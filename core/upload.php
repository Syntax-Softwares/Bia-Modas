<?php

require_once __DIR__ . '/helpers.php';

/**
 * Erro de validação/armazenamento de upload com mensagem pronta para o usuário.
 */
class UploadException extends RuntimeException
{
}

/**
 * Valida e armazena uploads de imagem de produto em public/img/produtos.
 */
class Upload
{
    private const TIPOS_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const TAMANHO_MAXIMO = 2 * 1024 * 1024; // 2 MB

    /**
     * Salva o arquivo e retorna o caminho público (ex.: /img/produtos/produto_...jpg).
     * Retorna null quando nenhum arquivo foi enviado.
     *
     * @throws UploadException
     */
    public static function imagemProduto(?array $arquivo): ?string
    {
        if ($arquivo === null || !isset($arquivo['error']) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ((int) $arquivo['error'] !== UPLOAD_ERR_OK) {
            throw new UploadException('Não foi possível enviar a imagem. Tente novamente.');
        }

        if ((int) ($arquivo['size'] ?? 0) > self::TAMANHO_MAXIMO) {
            throw new UploadException('A imagem deve ter no máximo 2 MB.');
        }

        if (!is_uploaded_file((string) $arquivo['tmp_name'])) {
            throw new UploadException('Arquivo de imagem inválido.');
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $arquivo['tmp_name']) ?: '';

        if (!isset(self::TIPOS_PERMITIDOS[$mime])) {
            throw new UploadException('Envie uma imagem JPG, PNG ou WEBP.');
        }

        $diretorio = __DIR__ . '/../public/img/produtos';

        if (!is_dir($diretorio) && !mkdir($diretorio, 0775, true) && !is_dir($diretorio)) {
            throw new UploadException('Não foi possível preparar a pasta de imagens.');
        }

        $nome = 'produto_' . date('YmdHis') . '_' . bin2hex(random_bytes(6))
            . '.' . self::TIPOS_PERMITIDOS[$mime];

        if (!move_uploaded_file((string) $arquivo['tmp_name'], $diretorio . '/' . $nome)) {
            throw new UploadException('Não foi possível salvar a imagem enviada.');
        }

        return '/img/produtos/' . $nome;
    }

    /**
     * Remove um arquivo de imagem de produto. Só atua dentro de /img/produtos.
     */
    public static function removerImagem(?string $caminho): void
    {
        if ($caminho === null || $caminho === '') {
            return;
        }

        $caminho = str_replace('\\', '/', $caminho);

        if (!str_starts_with($caminho, '/img/produtos/')) {
            return;
        }

        $arquivo = __DIR__ . '/../public' . $caminho;

        if (is_file($arquivo)) {
            @unlink($arquivo);
        }
    }
}
