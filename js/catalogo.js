/**
 * Bia Modas - Catálogo de Produtos
 * ================================
 * Centraliza todos os produtos da loja para uso nas páginas de categoria,
 * recomendações e filtros.
 */

const PRODUTOS = [
    {
        id: '655763783_18587522434040712_5117797556536014863_n',
        nome: 'Conjunto Social Marrom',
        preco: 249.9,
        precoOriginal: null,
        precoFormatado: 'R$ 249,90',
        imagem: './img/produtos/655763783_18587522434040712_5117797556536014863_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Marrom',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '656166026_18573763729040712_759498418098033265_n',
        nome: 'Vestido Longo Onça',
        preco: 179.9,
        precoOriginal: 229.9,
        precoFormatado: 'R$ 179,90',
        imagem: './img/produtos/656166026_18573763729040712_759498418098033265_n.jpg',
        categoria: 'Vestidos',
        tipo: 'Vestidos',
        cor: 'Estampado',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: '-22%'
    },
    {
        id: '656202496_18587522443040712_9008272828061942804_n',
        nome: 'Body Preto Manga Longa',
        preco: 89.9,
        precoOriginal: null,
        precoFormatado: 'R$ 89,90',
        imagem: './img/produtos/656202496_18587522443040712_9008272828061942804_n.jpg',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '656432116_18573763876040712_4770343261418897423_n',
        nome: 'Camisa Poá Bordô',
        preco: 119.9,
        precoOriginal: null,
        precoFormatado: 'R$ 119,90',
        imagem: './img/produtos/656432116_18573763876040712_4770343261418897423_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Vermelho',
        estilo: 'Romântico',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '656650964_18573763798040712_693249757322836689_n',
        nome: 'Vestido Jeans Curto',
        preco: 129.9,
        precoOriginal: 169.9,
        precoFormatado: 'R$ 129,90',
        imagem: './img/produtos/656650964_18573763798040712_693249757322836689_n.jpg',
        categoria: 'Vestidos',
        tipo: 'Vestidos',
        cor: 'Jeans',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: '-24%'
    },
    {
        id: '657389963_18577711444040712_4903765406060661891_n',
        nome: 'Camisa Social Marrom',
        preco: 109.9,
        precoOriginal: null,
        precoFormatado: 'R$ 109,90',
        imagem: './img/produtos/657389963_18577711444040712_4903765406060661891_n.jpg',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Marrom',
        estilo: 'Casual',
        tamanhos: ['G', 'GG', 'XG', 'XXG'],
        badge: ''
    },
    {
        id: '657539762_18573763816040712_7436584707047200505_n',
        nome: 'Blusa Canelada Marrom',
        preco: 79.9,
        precoOriginal: 99.9,
        precoFormatado: 'R$ 79,90',
        imagem: './img/produtos/657539762_18573763816040712_7436584707047200505_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Marrom',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: '-20%'
    },
    {
        id: '657598651_18573763777040712_6966380605204482899_n',
        nome: 'Blusa Animal Print',
        preco: 99.9,
        precoOriginal: null,
        precoFormatado: 'R$ 99,90',
        imagem: './img/produtos/657598651_18573763777040712_6966380605204482899_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Estampado',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '657729819_18573763867040712_1697772356227926921_n',
        nome: 'Camisa Poá Vermelha',
        preco: 119.9,
        precoOriginal: null,
        precoFormatado: 'R$ 119,90',
        imagem: './img/produtos/657729819_18573763867040712_1697772356227926921_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Vermelho',
        estilo: 'Romântico',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '657820557_18577711375040712_2935750976970278950_n',
        nome: 'Conjunto Saia Marrom',
        preco: 159.9,
        precoOriginal: 199.9,
        precoFormatado: 'R$ 159,90',
        imagem: './img/produtos/657820557_18577711375040712_2935750976970278950_n.jpg',
        categoria: 'Plus Size',
        tipo: 'Conjuntos',
        cor: 'Marrom',
        estilo: 'Casual',
        tamanhos: ['G', 'GG', 'XG', 'XXG'],
        badge: '-20%'
    },
    {
        id: '659331847_18577711228040712_207586077417158524_n',
        nome: 'Conjunto Moletom Rosê',
        preco: 139.9,
        precoOriginal: null,
        precoFormatado: 'R$ 139,90',
        imagem: './img/produtos/659331847_18577711228040712_207586077417158524_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Rosa',
        estilo: 'Esportivo',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: 'Novo'
    },
    {
        id: '660120340_18580649032040712_2327605271232299418_n',
        nome: 'Blusa Gola Alta Branca',
        preco: 89.9,
        precoOriginal: null,
        precoFormatado: 'R$ 89,90',
        imagem: './img/produtos/660120340_18580649032040712_2327605271232299418_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Branco',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: 'Novo'
    },
    {
        id: '661169861_18573763807040712_2163788536673407058_n',
        nome: 'Vestido Jeans Curto',
        preco: 129.9,
        precoOriginal: 169.9,
        precoFormatado: 'R$ 129,90',
        imagem: './img/produtos/661169861_18573763807040712_2163788536673407058_n.jpg',
        categoria: 'Vestidos',
        tipo: 'Vestidos',
        cor: 'Jeans',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: '-24%'
    },
    {
        id: '662020798_18577711387040712_2068533779891762205_n',
        nome: 'Blusa Marrom Gola Redonda',
        preco: 79.9,
        precoOriginal: null,
        precoFormatado: 'R$ 79,90',
        imagem: './img/produtos/662020798_18577711387040712_2068533779891762205_n.jpg',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Marrom',
        estilo: 'Casual',
        tamanhos: ['G', 'GG', 'XG', 'XXG'],
        badge: ''
    },
    {
        id: '662211355_18577711363040712_7694680724075539718_n',
        nome: 'Jaqueta Couro Preta',
        preco: 199.9,
        precoOriginal: null,
        precoFormatado: 'R$ 199,90',
        imagem: './img/produtos/662211355_18577711363040712_7694680724075539718_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Vintage',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '662858455_18577711330040712_4708135202645298654_n',
        nome: 'Blusa Canelada Marrom',
        preco: 89.9,
        precoOriginal: null,
        precoFormatado: 'R$ 89,90',
        imagem: './img/produtos/662858455_18577711330040712_4708135202645298654_n.jpg',
        categoria: 'Plus Size',
        tipo: 'Blusas',
        cor: 'Marrom',
        estilo: 'Casual',
        tamanhos: ['G', 'GG', 'XG', 'XXG'],
        badge: ''
    },
    {
        id: '663165894_18577711297040712_3855995851393585690_n',
        nome: 'Blusa Gola Alta Bege',
        preco: 79.9,
        precoOriginal: null,
        precoFormatado: 'R$ 79,90',
        imagem: './img/produtos/663165894_18577711297040712_3855995851393585690_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Bege',
        estilo: 'Minimalista',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '663197905_18577711219040712_3992758836225461434_n',
        nome: 'Conjunto Moletom Rosê',
        preco: 139.9,
        precoOriginal: null,
        precoFormatado: 'R$ 139,90',
        imagem: './img/produtos/663197905_18577711219040712_3992758836225461434_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Rosa',
        estilo: 'Esportivo',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: 'Novo'
    },
    {
        id: '670147132_18577711309040712_9135976680544019447_n',
        nome: 'Blusa Gola Alta Bege',
        preco: 79.9,
        precoOriginal: null,
        precoFormatado: 'R$ 79,90',
        imagem: './img/produtos/670147132_18577711309040712_9135976680544019447_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Bege',
        estilo: 'Minimalista',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '670303168_18586731355040712_3317129960623501582_n',
        nome: 'Camisa Xadrez Preta',
        preco: 119.9,
        precoOriginal: null,
        precoFormatado: 'R$ 119,90',
        imagem: './img/produtos/670303168_18586731355040712_3317129960623501582_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: ''
    },
    {
        id: '670394453_18577711366040712_4631773348593910628_n',
        nome: 'Calça Cargo Bege',
        preco: 159.9,
        precoOriginal: 199.9,
        precoFormatado: 'R$ 159,90',
        imagem: './img/produtos/670394453_18577711366040712_4631773348593910628_n.jpg',
        categoria: 'Calças',
        tipo: 'Calças',
        cor: 'Bege',
        estilo: 'Casual',
        tamanhos: ['36', '38', '40', '42', '44', '46'],
        badge: '-20%'
    },
    {
        id: '670407822_18586731364040712_651977335953068708_n',
        nome: 'Calça Jeans Wide Leg',
        preco: 149.9,
        precoOriginal: null,
        precoFormatado: 'R$ 149,90',
        imagem: './img/produtos/670407822_18586731364040712_651977335953068708_n.jpg',
        categoria: 'Calças',
        tipo: 'Calças',
        cor: 'Jeans',
        estilo: 'Casual',
        tamanhos: ['36', '38', '40', '42', '44', '46'],
        badge: 'Novo'
    },
    {
        id: '670413793_18586731391040712_2971456660877126061_n',
        nome: 'Conjunto Social Marrom',
        preco: 279.9,
        precoOriginal: 349.9,
        precoFormatado: 'R$ 279,90',
        imagem: './img/produtos/670413793_18586731391040712_2971456660877126061_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Marrom',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: '-20%'
    },
    {
        id: '670419882_18577711360040712_3093543179046810338_n',
        nome: 'Blusa Canelada Marrom',
        preco: 79.9,
        precoOriginal: null,
        precoFormatado: 'R$ 79,90',
        imagem: './img/produtos/670419882_18577711360040712_3093543179046810338_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Marrom',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '670431327_18586731382040712_5006795616984798374_n',
        nome: 'Saia Couro Preta',
        preco: 119.9,
        precoOriginal: 149.9,
        precoFormatado: 'R$ 119,90',
        imagem: './img/produtos/670431327_18586731382040712_5006795616984798374_n.jpg',
        categoria: 'Saias',
        tipo: 'Saias',
        cor: 'Preto',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: '-20%'
    },
    {
        id: '670460732_18586731334040712_4622915985530912812_n',
        nome: 'Conjunto Social Preto',
        preco: 299.9,
        precoOriginal: null,
        precoFormatado: 'R$ 299,90',
        imagem: './img/produtos/670460732_18586731334040712_4622915985530912812_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Preto',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '670517171_18577711438040712_9122070279626613748_n',
        nome: 'Camisa Social Rosa',
        preco: 99.9,
        precoOriginal: null,
        precoFormatado: 'R$ 99,90',
        imagem: './img/produtos/670517171_18577711438040712_9122070279626613748_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Rosa',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '670643116_18577711270040712_4125657114981036381_n',
        nome: 'Conjunto Plus Size Bege',
        preco: 189.9,
        precoOriginal: null,
        precoFormatado: 'R$ 189,90',
        imagem: './img/produtos/670643116_18577711270040712_4125657114981036381_n.jpg',
        categoria: 'Plus Size',
        tipo: 'Conjuntos',
        cor: 'Bege',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '670648115_18577711411040712_9011143600728075283_n',
        nome: 'Conjunto Esportivo Verde',
        preco: 159.9,
        precoOriginal: null,
        precoFormatado: 'R$ 159,90',
        imagem: './img/produtos/670648115_18577711411040712_9011143600728075283_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Verde',
        estilo: 'Esportivo',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '670660149_18580649050040712_1718079630445820814_n',
        nome: 'Blusa Texturizada Preta',
        preco: 89.9,
        precoOriginal: 109.9,
        precoFormatado: 'R$ 89,90',
        imagem: './img/produtos/670660149_18580649050040712_1718079630445820814_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: '-18%'
    },
    {
        id: '670977164_18580649005040712_5852872655235177723_n',
        nome: 'Blusa Tricô Gola Alta Bege',
        preco: 119.9,
        precoOriginal: null,
        precoFormatado: 'R$ 119,90',
        imagem: './img/produtos/670977164_18580649005040712_5852872655235177723_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Bege',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '671064037_18580649041040712_4075390779235412184_n',
        nome: 'Saia Couro Preta',
        preco: 119.9,
        precoOriginal: null,
        precoFormatado: 'R$ 119,90',
        imagem: './img/produtos/671064037_18580649041040712_4075390779235412184_n.jpg',
        categoria: 'Saias',
        tipo: 'Saias',
        cor: 'Preto',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '671112481_18577711252040712_6393062624550921342_n',
        nome: 'Conjunto Plus Size Bege',
        preco: 189.9,
        precoOriginal: null,
        precoFormatado: 'R$ 189,90',
        imagem: './img/produtos/671112481_18577711252040712_6393062624550921342_n.jpg',
        categoria: 'Plus Size',
        tipo: 'Conjuntos',
        cor: 'Bege',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: ''
    },
    {
        id: '671136273_18577711420040712_8592843297735420137_n',
        nome: 'Conjunto Esportivo Verde',
        preco: 159.9,
        precoOriginal: null,
        precoFormatado: 'R$ 159,90',
        imagem: './img/produtos/671136273_18577711420040712_8592843297735420137_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Verde',
        estilo: 'Esportivo',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '671745416_18582607435040712_190100085083067256_n',
        nome: 'Conjunto Plus Size Bicolor',
        preco: 219.9,
        precoOriginal: null,
        precoFormatado: 'R$ 219,90',
        imagem: './img/produtos/671745416_18582607435040712_190100085083067256_n.jpg',
        categoria: 'Plus Size',
        tipo: 'Conjuntos',
        cor: 'Preto',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '671899500_18580649023040712_8559865803877799356_n',
        nome: 'Blusa Gola Alta Branca',
        preco: 89.9,
        precoOriginal: null,
        precoFormatado: 'R$ 89,90',
        imagem: './img/produtos/671899500_18580649023040712_8559865803877799356_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Branco',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '672328011_18580648996040712_8466195470436935411_n',
        nome: 'Blusa Tricô Gola Alta Bege',
        preco: 119.9,
        precoOriginal: null,
        precoFormatado: 'R$ 119,90',
        imagem: './img/produtos/672328011_18580648996040712_8466195470436935411_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Bege',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '673189175_18587522401040712_5919760069831626742_n',
        nome: 'Conjunto Social Marrom',
        preco: 289.9,
        precoOriginal: 359.9,
        precoFormatado: 'R$ 289,90',
        imagem: './img/produtos/673189175_18587522401040712_5919760069831626742_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Marrom',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: '-19%'
    },
    {
        id: '673260984_18580649014040712_4327349068603948369_n',
        nome: 'Blusa Gola Alta Branca',
        preco: 89.9,
        precoOriginal: null,
        precoFormatado: 'R$ 89,90',
        imagem: './img/produtos/673260984_18580649014040712_4327349068603948369_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Branco',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: ''
    },
    {
        id: '682102794_18582607462040712_9081933668023055675_n',
        nome: 'Vestido Tule Degradê',
        preco: 149.9,
        precoOriginal: null,
        precoFormatado: 'R$ 149,90',
        imagem: './img/produtos/682102794_18582607462040712_9081933668023055675_n.jpg',
        categoria: 'Vestidos',
        tipo: 'Vestidos',
        cor: 'Marrom',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG'],
        badge: 'Novo'
    },
    {
        id: '682710293_18582607444040712_1235039031968563093_n',
        nome: 'Conjunto Couro Preto Bege',
        preco: 259.9,
        precoOriginal: 324.9,
        precoFormatado: 'R$ 259,90',
        imagem: './img/produtos/682710293_18582607444040712_1235039031968563093_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Preto',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: '-20%'
    },
    {
        id: '682818276_18582607471040712_1237684732226028984_n',
        nome: 'Calça Flare Vinho',
        preco: 129.9,
        precoOriginal: 159.9,
        precoFormatado: 'R$ 129,90',
        imagem: './img/produtos/682818276_18582607471040712_1237684732226028984_n.jpg',
        categoria: 'Calças',
        tipo: 'Calças',
        cor: 'Vermelho',
        estilo: 'Elegante',
        tamanhos: ['36', '38', '40', '42', '44', '46'],
        badge: '-20%'
    },
    {
        id: '682982345_18582607414040712_6152058859851151506_n',
        nome: 'Conjunto Couro Marrom',
        preco: 249.9,
        precoOriginal: null,
        precoFormatado: 'R$ 249,90',
        imagem: './img/produtos/682982345_18582607414040712_6152058859851151506_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Marrom',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '683440809_18582607522040712_3025202322902037125_n',
        nome: 'Blusa Tule Preta',
        preco: 119.9,
        precoOriginal: null,
        precoFormatado: 'R$ 119,90',
        imagem: './img/produtos/683440809_18582607522040712_3025202322902037125_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '683967425_18582607480040712_3834882235091960366_n',
        nome: 'Blusa Laço Preta',
        preco: 109.9,
        precoOriginal: null,
        precoFormatado: 'R$ 109,90',
        imagem: './img/produtos/683967425_18582607480040712_3834882235091960366_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: ''
    },
    {
        id: '684146986_18582607489040712_1782324324990995387_n',
        nome: 'Camisa Alongada Preta',
        preco: 129.9,
        precoOriginal: null,
        precoFormatado: 'R$ 129,90',
        imagem: './img/produtos/684146986_18582607489040712_1782324324990995387_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: ''
    },
    {
        id: '684401781_18582607501040712_5169914425854877907_n',
        nome: 'Camisa Alongada Preta',
        preco: 119.9,
        precoOriginal: null,
        precoFormatado: 'R$ 119,90',
        imagem: './img/produtos/684401781_18582607501040712_5169914425854877907_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: ''
    },
    {
        id: '684760661_18582607453040712_943410346854778167_n',
        nome: 'Vestido Tule Marrom',
        preco: 149.9,
        precoOriginal: 179.9,
        precoFormatado: 'R$ 149,90',
        imagem: './img/produtos/684760661_18582607453040712_943410346854778167_n.jpg',
        categoria: 'Vestidos',
        tipo: 'Vestidos',
        cor: 'Marrom',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: '-15%'
    },
    {
        id: '686797299_18582607426040712_619473844024588183_n',
        nome: 'Conjunto Couro Marrom',
        preco: 249.9,
        precoOriginal: null,
        precoFormatado: 'R$ 249,90',
        imagem: './img/produtos/686797299_18582607426040712_619473844024588183_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Marrom',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '687243777_18587522362040712_1508478109097199937_n',
        nome: 'Blazer Social Preto',
        preco: 189.9,
        precoOriginal: 229.9,
        precoFormatado: 'R$ 189,90',
        imagem: './img/produtos/687243777_18587522362040712_1508478109097199937_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Preto',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: '-15%'
    },
    {
        id: '687506768_18587522422040712_1465828937258330440_n',
        nome: 'Conjunto Social Bege',
        preco: 299.9,
        precoOriginal: 349.9,
        precoFormatado: 'R$ 299,90',
        imagem: './img/produtos/687506768_18587522422040712_1465828937258330440_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Bege',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: '-15%'
    },
    {
        id: '687805528_18587522344040712_6686027011365187315_n',
        nome: 'Blazer Marrom',
        preco: 199.9,
        precoOriginal: null,
        precoFormatado: 'R$ 199,90',
        imagem: './img/produtos/687805528_18587522344040712_6686027011365187315_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Marrom',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: ''
    },
    {
        id: '692544231_18586731373040712_2219992187691794873_n',
        nome: 'Conjunto Social Bege',
        preco: 289.9,
        precoOriginal: null,
        precoFormatado: 'R$ 289,90',
        imagem: './img/produtos/692544231_18586731373040712_2219992187691794873_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Bege',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '696554125_18586731343040712_2361827012407827099_n',
        nome: 'Camisa Xadrez Preta',
        preco: 139.9,
        precoOriginal: null,
        precoFormatado: 'R$ 139,90',
        imagem: './img/produtos/696554125_18586731343040712_2361827012407827099_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: ''
    },
    {
        id: '698324810_18587522380040712_1360404467050186317_n',
        nome: 'Camisa Xadrez Preta',
        preco: 149.9,
        precoOriginal: null,
        precoFormatado: 'R$ 149,90',
        imagem: './img/produtos/698324810_18587522380040712_1360404467050186317_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: ''
    },
    {
        id: '698621656_18586731322040712_6239315426509153096_n',
        nome: 'Conjunto Social Marrom',
        preco: 279.9,
        precoOriginal: null,
        precoFormatado: 'R$ 279,90',
        imagem: './img/produtos/698621656_18586731322040712_6239315426509153096_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Marrom',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    },
    {
        id: '702176730_18587522371040712_1888693146393576607_n',
        nome: 'Camisa Xadrez Preta',
        preco: 129.9,
        precoOriginal: null,
        precoFormatado: 'R$ 129,90',
        imagem: './img/produtos/702176730_18587522371040712_1888693146393576607_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: ''
    },
    {
        id: '702501625_18587522353040712_1412636360257886150_n',
        nome: 'Camisa Xadrez Preta',
        preco: 139.9,
        precoOriginal: null,
        precoFormatado: 'R$ 139,90',
        imagem: './img/produtos/702501625_18587522353040712_1412636360257886150_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Preto',
        estilo: 'Casual',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: ''
    },
    {
        id: '702569022_18587522329040712_5552315002095402149_n',
        nome: 'Blazer Marrom',
        preco: 189.9,
        precoOriginal: null,
        precoFormatado: 'R$ 189,90',
        imagem: './img/produtos/702569022_18587522329040712_5552315002095402149_n.jpg',
        categoria: 'Conjuntos',
        tipo: 'Conjuntos',
        cor: 'Marrom',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: ''
    },
    {
        id: '702611507_18587522398040712_7839140292566599040_n',
        nome: 'Blusa Marsala',
        preco: 99.9,
        precoOriginal: null,
        precoFormatado: 'R$ 99,90',
        imagem: './img/produtos/702611507_18587522398040712_7839140292566599040_n.jpg',
        categoria: 'Blusas',
        tipo: 'Blusas',
        cor: 'Vermelho',
        estilo: 'Elegante',
        tamanhos: ['P', 'M', 'G', 'GG', 'XG'],
        badge: 'Novo'
    }
];

function getProdutos() {
    return PRODUTOS;
}

function getProdutosPorCategoria(categoria) {
    const cat = categoria.toLowerCase().trim();
    return PRODUTOS.filter(p => {
        return p.categoria.toLowerCase() === cat ||
               p.tipo.toLowerCase() === cat;
    });
}

function getProdutosEmPromocao() {
    return PRODUTOS.filter(p => {
        const b = p.badge || '';
        return b.includes('%') || b.includes('-');
    });
}

function getProdutosPorCor(cor) {
    return PRODUTOS.filter(p => p.cor.toLowerCase() === cor.toLowerCase());
}

function getProdutoPorId(id) {
    return PRODUTOS.find(p => p.id === id) || null;
}

function getCategorias() {
    const cats = new Set();
    PRODUTOS.forEach(p => {
        cats.add(p.categoria);
        cats.add(p.tipo);
    });
    return Array.from(cats).sort();
}

function getCores() {
    const cores = new Set();
    PRODUTOS.forEach(p => cores.add(p.cor));
    return Array.from(cores).sort();
}

function getEstilos() {
    const estilos = new Set();
    PRODUTOS.forEach(p => estilos.add(p.estilo));
    return Array.from(estilos).sort();
}

function getTamanhos() {
    const tamanhos = new Set();
    PRODUTOS.forEach(p => (p.tamanhos || []).forEach(t => tamanhos.add(t)));
    return Array.from(tamanhos).sort((a, b) => {
        const order = ['PP','P','M','G','GG','XG','XXG','34','36','38','40','42','44','46','48','50','52','54','56'];
        return order.indexOf(a) - order.indexOf(b);
    });
}

function filtrarProdutos(filtros) {
    let resultado = [...PRODUTOS];

    if (filtros.categoria && filtros.categoria !== 'todos') {
        const cat = filtros.categoria.toLowerCase();
        resultado = resultado.filter(p =>
            p.categoria.toLowerCase() === cat ||
            p.tipo.toLowerCase() === cat
        );
    }

    if (filtros.cores && filtros.cores.length > 0) {
        const coresLower = filtros.cores.map(c => c.toLowerCase());
        resultado = resultado.filter(p => coresLower.includes(p.cor.toLowerCase()));
    }

    if (filtros.estilos && filtros.estilos.length > 0) {
        const estilosLower = filtros.estilos.map(e => e.toLowerCase());
        resultado = resultado.filter(p => estilosLower.includes(p.estilo.toLowerCase()));
    }

    if (filtros.tamanhos && filtros.tamanhos.length > 0) {
        resultado = resultado.filter(p =>
            p.tamanhos.some(t => filtros.tamanhos.includes(t))
        );
    }

    if (filtros.precoMin !== undefined && filtros.precoMin !== null) {
        resultado = resultado.filter(p => p.preco >= filtros.precoMin);
    }

    if (filtros.precoMax !== undefined && filtros.precoMax !== null) {
        resultado = resultado.filter(p => p.preco <= filtros.precoMax);
    }

    if (filtros.promocao) {
        resultado = resultado.filter(p => {
            const b = p.badge || '';
            return b.includes('%') || b.includes('-');
        });
    }

    if (filtros.busca) {
        const termo = filtros.busca.toLowerCase();
        resultado = resultado.filter(p =>
            p.nome.toLowerCase().includes(termo) ||
            p.categoria.toLowerCase().includes(termo) ||
            p.tipo.toLowerCase().includes(termo) ||
            p.cor.toLowerCase().includes(termo) ||
            p.estilo.toLowerCase().includes(termo)
        );
    }

    if (filtros.ordenacao) {
        if (filtros.ordenacao === 'menor-preco') {
            resultado.sort((a, b) => a.preco - b.preco);
        } else if (filtros.ordenacao === 'maior-preco') {
            resultado.sort((a, b) => b.preco - a.preco);
        } else if (filtros.ordenacao === 'nome') {
            resultado.sort((a, b) => a.nome.localeCompare(b.nome));
        }
    }

    return resultado;
}

function buildProductCardHTML(product) {
    return `
        <div class="col-lg-4 col-md-6 col-6 mb-4" data-cor="${product.cor}" data-tipo="${product.tipo}" data-estilo="${product.estilo}" data-categoria="${product.categoria}">
            ${buildProductCardInner(product, { withLinks: true })}
        </div>
    `;
}

function buildCarouselSlideHTML(product) {
    return `
        <div class="carousel-slide">
            ${buildProductCardInner(product, { withLinks: false })}
        </div>
    `;
}

function buildProductCardInner(product, opts) {
    const withLinks = opts && opts.withLinks;
    const escapNome = product.nome.replace(/'/g, "\\'");
    const precoFmt = product.precoFormatado || product.preco || '';
    const isOnSale = product.precoOriginal != null;

    const precoOriginalStr = isOnSale
        ? `<span class="price-original">R$ ${Number(product.precoOriginal).toFixed(2).replace('.', ',')}</span>`
        : '';

    const economiaStr = isOnSale
        ? `<div class="price-savings">Economize R$ ${(product.precoOriginal - product.preco).toFixed(2).replace('.', ',')}</div>`
        : '';

    let badgeHtml = '';
    if (isOnSale) {
        const descBadge = product.badge && product.badge.includes('-')
            ? `${product.badge} OFF`
            : `-${Math.round((1 - product.preco / product.precoOriginal) * 100)}% OFF`;
        badgeHtml = `<span class="product-badge is-sale"><i class="bi bi-tag-fill"></i>${descBadge}</span>`;
    } else if (product.badge) {
        badgeHtml = `<span class="product-badge">${product.badge}</span>`;
    }

    const productUrl = withLinks ? buildProductUrl(product) : '';
    const imageInner = `<img src="${product.imagem}" alt="${product.nome}">`;
    const image = withLinks ? `<a href="${productUrl}">${imageInner}</a>` : imageInner;
    const title = withLinks
        ? `<a href="${productUrl}" class="product-title-link">${product.nome}</a>`
        : product.nome;

    return `
        <div class="product-card ${isOnSale ? 'is-on-sale' : ''}">
            <div class="product-image">
                ${image}
                ${badgeHtml}
                <div class="product-actions">
                    <button aria-label="Adicionar aos favoritos" data-fav-name="${escapNome}" onclick="toggleProductFavoriteInline(this, '${escapNome}', '${precoFmt}', '${product.imagem}', '${product.categoria}')"><i class="bi bi-heart"></i></button>
                    <button aria-label="Adicionar ao carrinho" onclick="addToCart('${escapNome}', '${precoFmt}', '${product.imagem}')"><i class="bi bi-cart-plus"></i></button>
                    <button aria-label="Visualizar"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <div class="product-info">
                <div class="product-category">${product.categoria}</div>
                <h3 class="product-title">${title}</h3>
                <div class="product-price">
                    ${precoOriginalStr}
                    <span class="price-current">${precoFmt}</span>
                </div>
                ${economiaStr}
            </div>
        </div>
    `;
}

function toggleProductFavoriteInline(btn, name, price, image, category) {
    const product = { name, price, image, category };
    toggleFavorite(product);
    if (isFavorite(name)) {
        btn.innerHTML = '<i class="bi bi-heart-fill"></i>';
        btn.classList.add('active');
    } else {
        btn.innerHTML = '<i class="bi bi-heart"></i>';
        btn.classList.remove('active');
    }
}
