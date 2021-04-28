CMS - Front Office

DOCUMENTAÇÃO DA API: admin.[dominio].ibiz.pt/api (Tem de estar logado para acessar a Doc da API)

## INSTALL DOMINIO #######################################################

1) Configurar DNS | c:/windows/system32/drivers/etc/host
   Criar o nome do domínio no ficheiro host
   Adicionar a linha "127.0.0.1 frontoffice.test" no fim do ficheiro

2) Configurar Apache | c:/xampp/apache/confg/extra/https-vhost.conf
   Adicionar as linhas no fim do ficheiro:

   <VirtualHost frontoffice.test:80>
       DocumentRoot "C:/xampp/htdocs/intouchbiz/frontoffice/public"
       ServerName frontoffice.test
   </VirtualHost>

   <VirtualHost {nome-do-dominio}.test:80>
       DocumentRoot "C:/xampp/htdocs/intouchbiz/{nome-do-dominio}/public"
       ServerName {nome-do-dominio}.test
   </VirtualHost>

## INSTALL FRONT OFFICE ##################################################
1) Realize o Clone do repositório GIT https://gitlab.com/ContentManagement/frontoffice.git na raiz do seu servidor web:
	1.1) Exemplo: git clone https://gitlab.com/ContentManagement/frontoffice.git

    1.2) OPCIONAL: Há uma branch "development" onde temos as versões mais atualizadas, porém não estão 100% testadas.
	    Para fazer download da branch "development", faça:
        1.2.1) Execute o comando "git branch development origin/development"
        1.2.2) Execute o comando "git checkout development"
        1.2.3) Execute o comando "git pull"

2) Executar o comando "composer install" no diretório do projeto

3) Configurar o ficheiro .ENV com os dados de acesso à API e hosts tanto da Back Office quanto do Front Office

## FUNCIONALIDADES REQUERIDAS ############################################

PARA TODOS OS PROJETOS É NECESSÁRIO:

1) Opção multi-idioma: Definido nas variáveis do .env
   INITIAL_LANGUAGE=pt (O idioma inicial do site) [Definida no .settings]
   DEFAULT_LANGUAGE=en (O idioma padrão, quando não usa nenhum identificado na barra de endereço)
   SUPPORTED_LOCALES=en|pt (Os idiomas aceitos pelo site - Para novos idiomas, verificar configurações em /_docs/supported_locales.txt)

2) SEO
   A configuração de SEO é feita no ficheiro .layout
   É também feita em cada página, pois deve-se passar as variáveis específicas de cada página para o SEO, como o title, description, image, etc
   Há um DOC de suporte em /_docs/SEO.txt

3) Livro de reclamações (Para sites Portugueses)
   Descrição: Colocar no rodapé do site
   LOGOTIPOS EM: https://www.consumidor.gov.pt/pagina-de-entrada/livro-de-reclamacoes-regras-de-utilizacao-do-logotipo-do-lre.aspx
   HREF (_blank): https://www.livroreclamacoes.pt/inicio/reclamacao
   REL: rel="nofollow" (adicionar essa TAG no link)
   Ex: <a rel="nofollow" href="https://www.livroreclamacoes.pt/inicio/reclamacao" target="_blank"><img src="https://www.consumidor.gov.pt/upload/membro.id/imagens/i010896.png"></a>

##########################################################################
Framework Base: Symfony 5.1 (PHP 7.3 ou superior)
View: Twig, css (com Bootstrap 4), Javascript (com jquery)
Base de dados: MySQL

0) O ficheiro base do Twig, que tem a estrutura do projeto com menu, cabeçalho e rodapé está em:
   Default: /templates/base.html.twig

1) Os ficheiros de traduções estão em:
   /translations

   * Para as traduções, usamos o nome em inglês, sem símbolos (!,?,., etc).
   Ex: {% trans %}Hello{% endtrans %}!
   Resultado:
       EN: Hello!
       PT: Olá!

  * Como escrevemos o texto normal em Inglês, usamos esse texto (sem símbolos) como chave de referência para outros idiomas.
    Nesse caso, apenas o ficheiro "messages.pt.yaml" tem conteúdo, pois é a tradução do texto que é escrito em Twig.
	Esse texto, por já ser escrito corretamente em Inglês, não precisa de tradução para Inglês.

2) Os ficheiros de Negócio estão organizados de acordo com seu funcionamento:
   A) Entity (Não usada no Front Office)
		Descrição: É a especificação da base de dados. Dos campos que poderão ser exibidos nos formulários e inseridos na base de dados.
		A Entity deve ser, quase que rigidamente, configurada com todos os campos e tipos que estarão na base dados.
		O Symfony tem um serviço (migration / opcional) que lê os ficheiros da Entity e cria/altera a base de dados a partir do que está definido no código PHP.
		Basicamente a Entity tem:
		- Nome base de todas as outras classes para compor todo o Script.
		- A referência do Repository (local onde será feita a comunicação com a base de dados (SQLs)
		- Nome e tipos dos campos
		- Funções de GET e SET para os campos

		Local: /src/Entity/[ModuleName]/[ClassName].php

   B) Controller
		Descrição: É onde é feita a lógica do Script.
		O Controller vai controlar as Routes, que são os URIs dos Scripts.

		Ex: Configuramos no Controller que, se a pessoa digitar o endereço http://frontoffice.test/product-detail/car, o sistema vai chamar o método "productDetail($referenceKey)" onde $referenceKey vale "car", e tudo que for executado dentro desse método será enviado ao ficheiro "/templates/product/index.html.twig".
		O Controller pode buscar os dados na API e receber variáveis do browser (POST ou GET). Se necessário, vai fazer fazer FOR, IF, WHILE, ou qualquer outra ação necessária com as variáveis.

		Local: /src/Controller/[ModuleName]/[ClassName]Controller.php

   C) Form (Não usada no Front Office)
		Descrição: É o ficheiro onde o Symfony constrói o formulário html <form></form>. Podemos especificar a ordem que os campos aparecem, as propriedades dos campos, as ligações com outras classes (por exemplo se for um SELECT que seleciona os dados de outra Entity).

		Local: /src/Form/[ModuleName]/[ClassName]Type.php

   D) Repository (Não usada no Front Office)
		Descrição: É o local onde são executados os SQLs.

		Local: /src/Repository/[ModuleName]/[ClassName]Repository.php

   E) Templates
		Descrição: É onde temos os ficheiros com HTML. As variáveis que são "enviadas" pelo Controller podem ser acessíveis nesses ficheiros por TWIG: Ex: {{ varName }}.
		Há também algumas estruturas lógicas que podem ser feitas como FOR, IF e outras funções acessíveis na documentação do TWIG.

		- https://twig.symfony.com/
		- https://twig.symfony.com/doc/3.x/

		Local: /templates/[moduleName]/index.html.twig

   F) JavaScript
		Descrição: É o local onde contruímos as funções em Javascript. Nesses ficheiros, acessamos as funções dentro de um Objeto JS, assim não corremos o risco de ter conflito com funções com o mesmo nome.

		Local: /public/assets/js/[scripts].js

        QUERYBIZ
		OBS: O ficheiro querybiz.js tem classes Javascript para várias funções já pre-definidas no Front Office.
		Usar essas funções é opcional, mas elas foram construídas para evitar retrabalho de funcionalidades padrão, como o envio de formulário, validação de campos, adicionar produto ao carrinho, etc
		IMPORTANTE: Esse ficheiro não deve ser modificado, pois ele é mantido dentro das versões do Front Office.
		Local: /public/assets/js/querybiz.js

   F) CSS
		Descrição: É o local onde temos as classes específicas de CSS.

        Local: /public/assets/css/[scripts].js

3) As funções Globais, que são usadas por vários Controladores como Currency, MoneyParser, StringFormat, etc... ficam dentro de:
   /src/Functions/[ClassName].php

4) Há uma Class Layout que é acessível por TWIG (LayoutFunctions.[methodName()]) e as funções dentro dela estão disponíveis para todos os ficheiros TWIG sem necessidade de passar no $this->render() do Controller.
	Ex: {{ LayoutFunctions.getProject(app.request, 'site_tite') }}

	Local: /src/Template/Layout.php