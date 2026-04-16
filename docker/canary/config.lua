-- config.lua estático — representa o servidor Canary remoto.
-- Este arquivo NÃO contém senhas. É lido pelo MyAAC para exibir
-- informações públicas do servidor (nome, IP, porta).
-- Atualize ip e serverName conforme seu servidor Canary.

serverName          = "OwlOT"
ip                  = "191.30.87.147"   -- IP público ou DNS do servidor Canary
port                = 7171
loginPort           = 7171
gamePort            = 7172
statusPort          = 7171
motd                = "Bem-vindo ao OwlOT!"

-- Usado pelo MyAAC para montar o link de download do cliente
clientVersion       = 1500   -- ajuste para a versão do seu cliente
