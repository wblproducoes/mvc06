-- Sistema Administrativo MVC - Dados Iniciais
-- Versão: 1.1.0
-- Dados padrão para funcionamento do sistema

-- --------------------------------------------------------
-- Inserir dados padrão nas tabelas de referência
-- --------------------------------------------------------

-- Gêneros
INSERT INTO `{prefix}genders` (`id`, `name`, `translate`, `description`) VALUES
(1, 'male', 'Masculino', 'Gênero masculino'),
(2, 'female', 'Feminino', 'Gênero feminino'),
(3, 'other', 'Outro', 'Outros gêneros'),
(4, 'not_informed', 'Não informado', 'Prefere não informar')
ON DUPLICATE KEY UPDATE 
  `name` = VALUES(`name`),
  `translate` = VALUES(`translate`),
  `description` = VALUES(`description`);

-- Níveis de Acesso
INSERT INTO `{prefix}levels` (`id`, `name`, `translate`, `description`) VALUES
(1, 'master', 'Master', 'Acesso total ao sistema'),
(2, 'admin', 'Administrador', 'Administrador do sistema'),
(3, 'direction', 'Direção', 'Direção escolar'),
(4, 'financial', 'Financeiro', 'Setor financeiro'),
(5, 'coordination', 'Coordenação', 'Coordenação pedagógica'),
(6, 'secretary', 'Secretaria', 'Secretaria escolar'),
(7, 'teacher', 'Professor', 'Professor'),
(8, 'employee', 'Funcionário', 'Funcionário geral'),
(9, 'student', 'Aluno', 'Aluno da escola'),
(10, 'guardian', 'Responsável', 'Responsável pelo aluno'),
(11, 'user', 'Usuário', 'Usuário comum')
ON DUPLICATE KEY UPDATE 
  `name` = VALUES(`name`),
  `translate` = VALUES(`translate`),
  `description` = VALUES(`description`);

-- Status
INSERT INTO `{prefix}status` (`id`, `name`, `translate`, `color`, `description`) VALUES
(1, 'active', 'Ativo', 'success', 'Registro ativo'),
(2, 'inactive', 'Inativo', 'warning', 'Registro inativo'),
(3, 'blocked', 'Bloqueado', 'danger', 'Registro bloqueado'),
(4, 'deleted', 'Excluído', 'dark', 'Registro excluído'),
(5, 'completed', 'Concluído', 'info', 'Registro concluído'),
(6, 'overdue', 'Vencido', 'danger', 'Registro vencido'),
(7, 'pending', 'Pendente', 'secondary', 'Aguardando aprovação'),
(8, 'suspended', 'Suspenso', 'warning', 'Temporariamente suspenso')
ON DUPLICATE KEY UPDATE 
  `name` = VALUES(`name`),
  `translate` = VALUES(`translate`),
  `color` = VALUES(`color`),
  `description` = VALUES(`description`);

-- Períodos Escolares
INSERT INTO `{prefix}school_periods` (`name`, `translate`, `description`) VALUES
('morning', 'Matutino', 'Período da manhã'),
('afternoon', 'Vespertino', 'Período da tarde'),
('evening', 'Noturno', 'Período da noite'),
('full_time', 'Integral', 'Período integral')
ON DUPLICATE KEY UPDATE 
  `translate` = VALUES(`translate`),
  `description` = VALUES(`description`);

-- Matérias Escolares
INSERT INTO `{prefix}school_subjects` (`name`, `translate`, `description`) VALUES
('portuguese', 'Português', 'Língua Portuguesa'),
('mathematics', 'Matemática', 'Matemática'),
('science', 'Ciências', 'Ciências Naturais'),
('history', 'História', 'História'),
('geography', 'Geografia', 'Geografia'),
('english', 'Inglês', 'Língua Inglesa'),
('spanish', 'Espanhol', 'Língua Espanhola'),
('physical_education', 'Educação Física', 'Educação Física'),
('arts', 'Artes', 'Educação Artística'),
('music', 'Música', 'Educação Musical'),
('philosophy', 'Filosofia', 'Filosofia'),
('sociology', 'Sociologia', 'Sociologia'),
('physics', 'Física', 'Física'),
('chemistry', 'Química', 'Química'),
('biology', 'Biologia', 'Biologia'),
('literature', 'Literatura', 'Literatura'),
('redaction', 'Redação', 'Produção Textual'),
('informatics', 'Informática', 'Informática'),
('religious_education', 'Ensino Religioso', 'Ensino Religioso'),
('environmental_education', 'Educação Ambiental', 'Educação Ambiental')
ON DUPLICATE KEY UPDATE 
  `translate` = VALUES(`translate`),
  `description` = VALUES(`description`);