🌱 Sistema Tempo de Semear - 2ª Edição
Sistema de cadastro único para o programa Tempo de Semear do Estado do Maranhão.


📋 Índice

Sobre o Projeto
Funcionalidades
Requisitos
Instalação
Configuração
Uso
Deploy em Produção
Estrutura do Projeto
Contribuindo
Suporte


📖 Sobre o Projeto
O Sistema Tempo de Semear é uma plataforma web desenvolvida para gerenciar cadastros de beneficiários do programa social homônimo. O sistema permite:

✅ Cadastro de beneficiários com validação de dados
✅ Upload de documentos digitalizados
✅ Painel administrativo para gestão de cadastros
✅ Controle de acesso com perfis (admin e supervisor)
✅ Exportação de dados para Excel
✅ Auditoria completa de alterações
✅ Sistema de protocolo único


⚡ Funcionalidades
Para Beneficiários:

📝 Cadastro completo com dados pessoais, documentos e endereço
📤 Upload de documentos (CPF, RG, comprovantes, etc.)
💾 Salvamento de rascunhos
🔒 Envio definitivo com bloqueio de edição
🔐 Login seguro com CPF/email e senha

Para Administradores:

📊 Dashboard com filtros por município e CPF
👁️ Visualização detalhada de cadastros
✏️ Edição de cadastros com justificativa
📥 Exportação completa para Excel
📜 Histórico de todas as alterações
🔐 Controle de acesso por perfil


💻 Requisitos
Desenvolvimento:

Docker 20.10+
Docker Compose 1.29+
Git

Produção:

Servidor Linux (Ubuntu 20.04+ recomendado)
Docker e Docker Compose
Certificado SSL válido
Mínimo 2GB RAM, 20GB disco


🚀 Instalação
1. Clone o repositório
bashgit clone https://github.com/seu-usuario/tempo-de-semear.git
cd tempo-de-semear
2. Configure o arquivo .env
bashcp .env.example .env
nano .env
Edite com suas configurações:
envDB_PASSWORD=SuaSenhaForte123!
DB_HOST=db
DB_PORT=5432
DB_NAME=semeadordb
DB_USER=semeadoruser
3. Inicie os containers
bash# Desenvolvimento
docker-compose up -d --build

# Aguarde alguns segundos para o banco inicializar
4. Execute as migrações do banco
bash# Criar tabelas
docker exec tds-postgres psql -U semeadoruser -d semeadordb -f /tmp/01_create-tables-sql_cp.sql

# Criar triggers
docker exec tds-postgres psql -U semeadoruser -d semeadordb -f /tmp/02_create-triggers-sql.sql

# Popular estados e municípios
docker exec tds-postgres psql -U semeadoruser -d semeadordb -f /tmp/03_insert_states_cities.sql
Nota: Antes de executar, copie os arquivos SQL para dentro do container:
bashdocker cp 01_create-tables-sql_cp.sql tds-postgres:/tmp/
docker cp 02_create-triggers-sql.sql tds-postgres:/tmp/
docker cp 03_insert_states_cities.sql tds-postgres:/tmp/
5. Crie o primeiro usuário administrador
bashdocker exec -it tds-php php /var/www/html/../scripts/criar_admin.php
Siga as instruções na tela para criar o admin.
6. Acesse o sistema

Área pública: http://localhost:8080
Área administrativa: http://localhost:8080/admin


⚙️ Configuração
Estrutura de Diretórios
tempo-de-semear/
├── src/                    # Código-fonte PHP
│   ├── admin/             # Área administrativa
│   ├── cadastro.php       # Formulário de registro
│   ├── login.php          # Login de beneficiários
│   ├── dashboard.php      # Painel do beneficiário
│   └── db.php             # Conexão com banco
├── nginx/                 # Configurações do Nginx
├── php/                   # Configurações do PHP
│   ├── Dockerfile
│   └── config/
├── uploads/               # Arquivos enviados (não versionado)
├── backups/              # Backups do sistema
├── scripts/              # Scripts auxiliares
├── docker-compose.yml    # Orquestração dos containers
└── composer.json         # Dependências PHP
Configurações do PHP
Edite php/config/uploads.ini para ajustar limites de upload:
iniupload_max_filesize = 10M
post_max_size = 25M
Configurações do Nginx
Edite nginx/default.conf para ajustar limites e timeouts.

📘 Uso
Fluxo do Beneficiário

Criar conta: Acesse /cadastro.php e crie uma conta com CPF, email e senha
Login: Faça login em /login.php
Preencher formulário: Complete todos os dados no dashboard
Enviar documentos: Faça upload dos documentos solicitados
Salvar rascunho: Salve e continue depois, ou
Enviar definitivo: Envie o cadastro (após isso, não pode mais editar)

Fluxo do Administrador

Login: Acesse /admin com suas credenciais
Visualizar cadastros: Navegue pela lista com filtros
Exportar dados: Clique em "Exportar para Excel"
Editar cadastro: Edite se necessário (com justificativa)
Ver histórico: Consulte o histórico de alterações


🌐 Deploy em Produção
Preparação

Configure o servidor

bash# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Docker
curl -fsSL https://get.docker.com | sudo sh

# Instalar Docker Compose
sudo apt install docker-compose -y

Obter certificado SSL

bash# Instalar Certbot
sudo apt install certbot -y

# Obter certificado
sudo certbot certonly --standalone -d tempodesemear.ma.gov.br

# Copiar certificados
sudo cp /etc/letsencrypt/live/tempodesemear.ma.gov.br/fullchain.pem ./ssl/cert.pem
sudo cp /etc/letsencrypt/live/tempodesemear.ma.gov.br/privkey.pem ./ssl/key.pem

Configure variáveis de ambiente

bash# Criar .env de produção
nano .env

# Usar senhas fortes!
DB_PASSWORD=$(openssl rand -base64 32)

Ajuste o docker-compose para produção

Use o arquivo docker-compose.prod.yml fornecido na documentação.

Inicie o sistema

bashdocker-compose -f docker-compose.prod.yml up -d --build

Configure backups automáticos

bash# Tornar script executável
chmod +x scripts/backup.sh

# Adicionar ao crontab
crontab -e
# Adicionar: 0 2 * * * /caminho/para/scripts/backup.sh
Checklist de Segurança

 SSL/HTTPS configurado
 Senhas fortes
 Firewall ativo (portas 80, 443, 22)
 Backups automáticos
 Logs monitorados
 Updates automáticos configurados
 Rate limiting ativo


🗂️ Estrutura do Banco de Dados
Principais Tabelas

beneficiarios: Usuários externos (cidadãos)
usuarios_internos: Administradores e supervisores
cadastros: Dados completos do cadastro
municipios_permitidos: Municípios participantes
arquivos_cadastro: Documentos enviados
historico_edicoes: Auditoria de alterações
logs_sistema: Logs de ações do sistema

Tipos ENUM

perfil_interno: admin, supervisor
status_cadastro: rascunho, enviado, em_analise, aprovado, rejeitado
tipo_documento: rg, cpf, comprovante_residencia, foto_3x4, caf, outros
tipo_sexo: M, F, Outro


🔧 Manutenção
Backup Manual
bash# Backup do banco
docker exec tds-postgres pg_dump -U semeadoruser semeadordb | gzip > backup_$(date +%Y%m%d).sql.gz

# Backup dos uploads
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz uploads/
Restaurar Backup
bash# Restaurar banco
gunzip < backup_20250103.sql.gz | docker exec -i tds-postgres psql -U semeadoruser semeadordb

# Restaurar uploads
tar -xzf uploads_backup_20250103.tar.gz
Ver Logs
bash# Logs do Nginx
docker logs tds-nginx

# Logs do PHP
docker logs tds-php

# Logs do PostgreSQL
docker logs tds-postgres

# Logs em tempo real
docker logs -f tds-nginx
Atualizar o Sistema
bash# Pull das últimas alterações
git pull origin main

# Rebuild dos containers
docker-compose down
docker-compose up -d --build

# Executar novas migrações se houver

🐛 Troubleshooting
Problema: Container não inicia
bash# Verificar logs
docker logs tds-postgres
docker logs tds-php
docker logs tds-nginx

# Verificar portas
sudo netstat -tulpn | grep :8080
Problema: Erro ao conectar no banco
bash# Testar conexão
docker exec tds-postgres psql -U semeadoruser -d semeadordb -c "SELECT version();"

# Verificar senha
docker exec tds-postgres env | grep POSTGRES
Problema: Upload não funciona
bash# Verificar permissões
docker exec tds-php ls -la /var/www/uploads

# Corrigir permissões
docker exec tds-php chown -R www-data:www-data /var/www/uploads
Problema: Exportação Excel falha
bash# Verificar se o PHPSpreadsheet está instalado
docker exec tds-php ls -la /var/www/html/vendor/phpoffice

# Reinstalar dependências
docker exec tds-php composer install --no-dev

📊 Monitoramento
Métricas Recomendadas

Uso de CPU e memória dos containers
Espaço em disco
Taxa de erros nos logs
Tempo de resposta das páginas
Número de cadastros por dia

Ferramentas Sugeridas

Portainer: Interface web para Docker
Grafana + Prometheus: Dashboards de métricas
Sentry: Rastreamento de erros
UptimeRobot: Monitoramento de disponibilidade


🤝 Contribuindo
Contribuições são bem-vindas! Por favor:

Fork o projeto
Crie uma branch para sua feature (git checkout -b feature/NovaFuncionalidade)
Commit suas mudanças (git commit -m 'Adiciona nova funcionalidade')
Push para a branch (git push origin feature/NovaFuncionalidade)
Abra um Pull Request


📄 Licença
Este projeto é propriedade do Governo do Estado do Maranhão.

📞 Suporte
Para dúvidas ou problemas:

Email: pablo.dias@sagrima.ma.gov.br



👥 Equipe
Pablo Dias
Desenvolvido para a Secretaria de Estado de Agricultura e Pecuaria

📝 Changelog
[2.0.0] - 2025-01-03

✨ Sistema completo de cadastro
✨ Painel administrativo
✨ Exportação para Excel
✨ Sistema de auditoria
✨ Upload de documentos
✨ Controle de municípios ativos


⚠️ IMPORTANTE: Este é um sistema governamental. Mantenha sempre a segurança e privacidade dos dados dos cidadãos.