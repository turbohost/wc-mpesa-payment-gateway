=== Mobile M-Pesa Payment Gateway  ===
Contributors: karson9
Author URI: http://karsonadam.com/
Plugin URL: https://wordpress.org/plugins/wc-m-pesa-payment-gateway/
Tags:  mpesa, m-pesa, woocommerce, mpesa api, gateway, Mobile Payments, Mozambique, mpesa online, Mpesa API Mozambique
Requires at least: 5.0
Tested up to: 5.4
Requires PHP: 7.0
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Receba pagamentos de clientes Mpesa diretamente em sua loja virtual usando a API M-Pesa da Vodacom Moçambique.

== Description ==

O plugin *Mobile M-Pesa Payment Gateway* é uma extensão para WooCommerce e WordPress que permite que você receba pagamentos diretamente em sua loja virtual através da API M-Pesa da Vodacom Moçambique.
Por meio do *Mobile M-Pesa Payment Gateway*, os compradores de sua loja podem comprar produtos ou serviços em sua loja usando um número de telefone associado à conta M-Pesa.


== Other Notes ==

### Pré requisitos
Para usar o plugin é necessário:
* Ter [WooCommerce](https://wordpress.org/plugins/woocommerce) instalado.
* Criar uma conta no [portal de desenvolvedores do M-pesa](https://developer.mpesa.vm.co.mz/) onde irá obter as credenciais necessárias para configurar a conta.

### Dúvidas

Se tiver  alguma dúvida :

* Visite a nossa sessão de [Perguntas Frequentes](https://wordpress.org/plugins/wc-m-pesa-payment-gateway/#faq).
* Utilize o nosso fórum no [Github](https://github.com/turbohost-co/wc-mpesa-payment-gateway/issues).
* Crie um tópico no [fórum de ajuda do WordPress](https://wordpress.org/support/plugin/wc-m-pesa-payment-gateway/).
* Você pode entrar em contato pelo WhatsApp ou SMS no número +258 84 3670 086.

### Contribuir

Você pode contribuir com o código fonte em nossa [página do GitHub](https://github.com/turbohost-co/wc-mpesa-payment-gateway).


Este plugin foi desenvolvido sem nenhum incentivo da Vodacom. Nenhum dos desenvolvedores deste plugin possuem vínculos com estas duas empresas.

 
== Installation ==

### Instalação automática

1. Faça login no seu painel do WordPress
2. Clique em *Plugins> Adicionar novo* no menu esquerdo.
3. Na caixa de pesquisa, digite **Mobile M-Pesa Payment Gateway**.
4. Clique em *Instalar agora* no **Mobile M-Pesa Payment Gateway** para instalar o plug-in no seu site e em seguida clique em  *ativar* o plug-in.
5. Clique em *WooCommerce> Configurações* no menu esquerdo e clique na guia *Pagamentos*.
6. Clique em **Mobile M-Pesa Payment Gateway** na lista dos métodos de pagamento disponíveis
7. Defina as configurações do Mobile M-Pesa Payment Gateway usando credenciais disponíveis em https://developer.mpesa.vm.co.mz/

 

### Instalação manual

Caso a instalação automática não funcione, faça o download do plug-in aqui usando o botão Download.

1. Descompacte o arquivo e carregue a pasta via FTP no diretório *wp-content/plugins* da sua instalação do WordPress.
2. Vá para *Plugins> Plugins instalados* e clique em *Ativar* no Mobile M-Pesa Payment Gateway.

== Screenshots ==


1. Lista dos método de pagamento com o  *Mobile M-Pesa Payment Gateway* ativo
2. Configuração das credenciais método de pagamento Mpesa.
3. Página da Finalização do pagamento com o método de pagamento selecionado com o campo para digitar o número do telefone mpesa
4. Página de pagamento com as instruções para que o cliente finalize o pagamento.

== Frequently Asked Questions ==

= Onde encontro as credenciais para configurar o plug-in? =

Para obter credenciais, crie uma conta em https://developer.mpesa.vm.co.mz/

= O que devo colocar no campo Código do provedor de serviços? =

* Se você estiver no ambiente de teste, use **171717**
* Se você estiver no ambiente de produção, use código de produção fornecido pela Vodacom.

== Changelog ==

= 1.2 =

* Feedback de erro aprimorado na página de pagamento
* Corrigido o erro de validação do certificado do servidor em ambientes *Windows*

= 1.0 =

Primeiro lançamento
