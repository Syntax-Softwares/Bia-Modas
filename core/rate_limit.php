<?php

/**
 * Rate limit em arquivo (com flock) + fallback em sessao.
 *
 * Usado para:
 *  - limitar requisicoes por IP (mitigacao de flood/DDoS na camada da app);
 *  - limitar tentativas de senha por usuario e por usuario+IP (brute force).
 *
 * Nenhum IP e gravado em texto puro: a chave e sempre um hash SHA-256.
 */
final class RateLimit
{
    private const MAX_CHAVES = 8000;

    /**
     * Hash do IP do cliente.
     *
     * Usa REMOTE_ADDR de proposito: X-Forwarded-For pode ser forjado. Se a
     * aplicacao rodar atras de um proxy confiavel, o proxy deve reescrever
     * REMOTE_ADDR (ou validar a cadeia antes de confiar no cabecalho).
     */
    public static function ip(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        return hash('sha256', 'ip|' . $ip);
    }

    /**
     * Consome uma unidade do limite. Retorna false quando estourou.
     */
    public static function consumir(string $bucket, string $chave, int $max, int $janela): bool
    {
        $max = max(1, $max);
        $janela = max(1, $janela);

        $resultado = self::transacao(
            $bucket,
            static function (array &$mapa) use ($chave, $max, $janela): bool {
                $agora = time();
                self::podar($mapa, $agora - $janela);
                $k = hash('sha256', $chave);
                $marcas = $mapa[$k] ?? [];

                if (count($marcas) >= $max) {
                    return false;
                }

                $marcas[] = $agora;
                $mapa[$k] = $marcas;

                return true;
            }
        );

        if ($resultado === null) {
            // Storage indisponivel: prioriza disponibilidade, mas registra.
            error_log('[seguranca] rate limit indisponivel bucket=' . $bucket);

            return true;
        }

        if ($resultado === false && mt_rand(1, 10) === 1) {
            error_log('[seguranca] rate limit bucket=' . $bucket . ' chave=' . substr(hash('sha256', $chave), 0, 16));
        }

        return $resultado;
    }

    public static function registrar(string $bucket, string $chave, int $janela): void
    {
        $janela = max(1, $janela);

        self::transacao(
            $bucket,
            static function (array &$mapa) use ($chave, $janela): bool {
                $agora = time();
                self::podar($mapa, $agora - $janela);
                $k = hash('sha256', $chave);
                $mapa[$k][] = $agora;

                return true;
            }
        );

        self::registrarSessao($bucket, $chave, $janela);
    }

    public static function contar(string $bucket, string $chave, int $janela): int
    {
        $janela = max(1, $janela);

        $contagem = self::transacao(
            $bucket,
            static function (array &$mapa) use ($chave, $janela): int {
                self::podar($mapa, time() - $janela);
                $k = hash('sha256', $chave);

                return count($mapa[$k] ?? []);
            }
        );

        return max((int) ($contagem ?? 0), self::contarSessao($bucket, $chave, $janela));
    }

    public static function limpar(string $bucket, string $chave): void
    {
        self::transacao(
            $bucket,
            static function (array &$mapa) use ($chave): bool {
                unset($mapa[hash('sha256', $chave)]);

                return true;
            }
        );

        self::limparSessao($bucket, $chave);
    }

    private static function transacao(string $bucket, callable $acao): mixed
    {
        if (preg_match('/^[a-z0-9_]{1,40}$/', $bucket) !== 1) {
            return null;
        }

        $diretorio = self::diretorio();

        if ($diretorio === null) {
            return null;
        }

        $caminho = $diretorio . '/' . $bucket . '.json';
        $ponteiro = @fopen($caminho, 'c+');

        if ($ponteiro === false) {
            return null;
        }

        if (!flock($ponteiro, LOCK_EX)) {
            fclose($ponteiro);

            return null;
        }

        $conteudo = stream_get_contents($ponteiro);
        $mapa = [];

        if (is_string($conteudo) && $conteudo !== '') {
            $decodificado = json_decode($conteudo, true);

            if (is_array($decodificado)) {
                $mapa = $decodificado;
            }
        }

        $resultado = $acao($mapa);

        if (count($mapa) > self::MAX_CHAVES) {
            self::podarExcesso($mapa);
        }

        $json = json_encode($mapa, JSON_UNESCAPED_SLASHES);

        if (is_string($json)) {
            rewind($ponteiro);
            ftruncate($ponteiro, 0);
            fwrite($ponteiro, $json);
            fflush($ponteiro);
        }

        flock($ponteiro, LOCK_UN);
        fclose($ponteiro);

        return $resultado;
    }

    private static function podar(array &$mapa, int $limite): void
    {
        foreach ($mapa as $chave => $marcas) {
            if (!is_array($marcas)) {
                unset($mapa[$chave]);

                continue;
            }

            $validas = [];

            foreach ($marcas as $marca) {
                if (is_int($marca) && $marca > $limite) {
                    $validas[] = $marca;
                }
            }

            if ($validas === []) {
                unset($mapa[$chave]);
            } else {
                $mapa[$chave] = $validas;
            }
        }
    }

    private static function podarExcesso(array &$mapa): void
    {
        $ultimo = static function (array $marcas): int {
            return $marcas === [] ? 0 : (int) max($marcas);
        };

        uasort($mapa, static fn (array $a, array $b): int => $ultimo($a) <=> $ultimo($b));
        $mapa = array_slice($mapa, -self::MAX_CHAVES, null, true);
    }

    private static function diretorio(): ?string
    {
        // O bind mount do docker-compose pertence ao usuario do host; o Apache
        // roda como www-data. Tentamos ./storage e, se nao for gravavel,
        // usamos o diretorio temporario do proprio container.
        $candidatos = [
            dirname(__DIR__) . '/storage/ratelimit',
            sys_get_temp_dir() . '/biamodas-ratelimit',
        ];

        foreach ($candidatos as $diretorio) {
            if (!is_dir($diretorio) && !@mkdir($diretorio, 0700, true) && !is_dir($diretorio)) {
                continue;
            }

            if (is_writable($diretorio)) {
                return $diretorio;
            }
        }

        return null;
    }

    private static function registrarSessao(string $bucket, string $chave, int $janela): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $agora = time();
        $k = hash('sha256', $chave);
        $marcas = $_SESSION['_rl'][$bucket][$k] ?? [];

        if (!is_array($marcas)) {
            $marcas = [];
        }

        $validas = [];

        foreach ($marcas as $marca) {
            if (is_int($marca) && $marca > $agora - $janela) {
                $validas[] = $marca;
            }
        }

        $validas[] = $agora;
        $_SESSION['_rl'][$bucket][$k] = array_slice($validas, -50);
    }

    private static function contarSessao(string $bucket, string $chave, int $janela): int
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return 0;
        }

        $k = hash('sha256', $chave);
        $marcas = $_SESSION['_rl'][$bucket][$k] ?? [];

        if (!is_array($marcas)) {
            return 0;
        }

        $agora = time();
        $contagem = 0;

        foreach ($marcas as $marca) {
            if (is_int($marca) && $marca > $agora - $janela) {
                $contagem++;
            }
        }

        return $contagem;
    }

    private static function limparSessao(string $bucket, string $chave): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        unset($_SESSION['_rl'][$bucket][hash('sha256', $chave)]);
    }
}
