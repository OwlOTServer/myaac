Atue como um Engenheiro de Software e DevOps especialista em infraestrutura web e ecossistema OpenTibia.

Eu tenho um servidor de Tibia (engine Canary) e seu respectivo banco de dados (MySQL/MariaDB) já rodando nativamente no meu host (WSL/Ubuntu). Agora, preciso configurar o front-end (MyAAC) para rodar via Docker, conectando-se a essa base de dados existente e utilizando o template 'tibia.com'.

Por favor, gere os arquivos e instruções necessárias seguindo estes requisitos arquiteturais:

1. Dockerfile:
- Utilize uma imagem base oficial do PHP com Apache (ex: php:8.2-apache).
- Instale e habilite todas as extensões do PHP necessárias para o MyAAC funcionar perfeitamente com o Canary (mysqli, pdo_mysql, gd, xml, curl, mbstring, zip).
- Configure o `mod_rewrite` do Apache.
- Ajuste as permissões corretas para o usuário `www-data`.

2. docker-compose.yml:
- Crie o serviço web expondo a porta 80.
- Configure o mapeamento de volumes (bind mounts) para o diretório atual (onde o MyAAC está clonado) para `/var/www/html`.
- Crie um volume adicional de leitura (`ro`) apontando para o diretório do meu servidor Canary no host. O MyAAC precisa ler o arquivo `config.lua` para extrair as configurações.
- Configure a rede (`extra_hosts` ou network mode adequado) para que o container do MyAAC consiga se comunicar com o MySQL que está rodando no host WSL (localhost do host).

3. Configuração do MyAAC (config.local.php):
- Forneça o conteúdo inicial para criar ou injetar no `config.local.php` que defina o template como `tibia.com`.
- Explique como o MyAAC deve ser configurado via painel de instalação (ou via arquivo) para usar o IP correto do host (ex: host.docker.internal ou IP da bridge) em vez de 127.0.0.1, para alcançar o banco de dados do Canary.

Gere os códigos completos e documentados dos arquivos `Dockerfile` e `docker-compose.yml`, seguidos das instruções de execução.


E ao final de cada processo gere dentro da pasta .claude/ toda a "memoria" para voce sempre saber do contexto e economizar tokens