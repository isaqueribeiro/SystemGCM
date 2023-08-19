USE [SystemGCM]
GO

CREATE OR ALTER PROCEDURE dbo.spDocumentarCampo
	@source_table	nvarchar (386)
  , @source_field	nvarchar (386)
  , @description	nvarchar (386)
AS
BEGIN
  Declare @existe nvarchar (386);

  if ((@source_table <> '') and (@source_field <> '') and (@description <> ''))
  Begin
	Select 
	  @existe = x.name
	from fn_listextendedproperty('MS_Description', 'SCHEMA', 'dbo', 'TABLE', @source_table, 'COLUMN', @source_field) x;
	
	If (coalesce(@existe, '') <> '') 
	Begin
	  EXEC sys.sp_dropextendedproperty 
		  @name=N'MS_Description'
		, @level0type=N'SCHEMA'
		, @level0name=N'dbo'
		, @level1type=N'TABLE'
		, @level1name=@source_table
		, @level2type=N'COLUMN'
		, @level2name=@source_field;
    End;

	EXEC sys.sp_addextendedproperty 
		  @name=N'MS_Description'
		, @value=@description
		, @level0type=N'SCHEMA'
		, @level0name=N'dbo'
		, @level1type=N'TABLE'
		, @level1name=@source_table
		, @level2type=N'COLUMN'
		, @level2name=@source_field
  End
END
GO

CREATE OR ALTER VIEW dbo.vw_guid_id
AS
  SELECT '{' + CAST(NEWID() AS varchar(38)) + '}' AS guid_id
GO

IF OBJECT_ID (N'dbo.ufnGetGuidID', N'FN') IS NOT NULL  
    DROP FUNCTION dbo.ufnGetGuidID;  
GO  
CREATE OR ALTER FUNCTION dbo.ufnGetGuidID()
RETURNS varchar(38)
AS   
BEGIN  
  DECLARE @ret varchar(38);  
  SELECT @ret = guid_id from dbo.vw_guid_id;
  RETURN @ret;  
END;
GO

IF OBJECT_ID (N'dbo.ufnRemoverAcentos', N'FN') IS NOT NULL  
    DROP FUNCTION dbo.ufnRemoverAcentos;  
GO  
CREATE OR ALTER FUNCTION dbo.ufnRemoverAcentos(@Texto VARCHAR(MAX))
RETURNS varchar(Max)
AS   
BEGIN  
  DECLARE @TEXTO_FORMATADO VARCHAR(MAX);  

  -- O trecho abaixo possibilita que caracteres como "º" ou "ª"
  -- sejam convertidos para "o" ou "a", respectivamente
  SET @TEXTO_FORMATADO = UPPER(@TEXTO)
	COLLATE sql_latin1_general_cp1250_ci_as;

  -- O trecho abaixo remove acentos e outros caracteres especiais,
  -- substituindo os mesmos por letras normais
  SET @TEXTO_FORMATADO = @TEXTO_FORMATADO
	COLLATE sql_latin1_general_cp1251_ci_as;
 
  RETURN @TEXTO_FORMATADO;
END;
GO

/*Criar Tabelas*/

IF OBJECT_ID (N'dbo.sys_empresa') IS NULL
BEGIN
	CREATE TABLE dbo.sys_empresa (
		id_empresa	VARCHAR(38) PRIMARY KEY 
	  , cd_empresa	INT IDENTITY(1,1) NOT NULL UNIQUE
	  , nm_empresa	VARCHAR(250)
	  , nm_fantasia	VARCHAR(150)
	  , nr_cnpj_cpf	VARCHAR(25) UNIQUE
	  , cd_estado	INT
	  , cd_cidade	INT
	);

	ALTER TABLE dbo.sys_empresa 
	  add ds_endereco VARCHAR(250);

	ALTER TABLE dbo.sys_empresa 
	  add ds_contatos VARCHAR(250);

	ALTER TABLE dbo.sys_empresa 
	  add ds_email VARCHAR(100);
END
GO

IF OBJECT_ID (N'dbo.sys_perfil') IS NULL
	CREATE TABLE dbo.sys_perfil (
		cd_perfil	INT NOT NULL PRIMARY KEY 
	  , ds_perfil	VARCHAR(50)
	  , sn_ativo	SMALLINT DEFAULT 1 NOT NULL
	);

IF OBJECT_ID (N'dbo.sys_usuario') IS NULL
BEGIN
	CREATE TABLE dbo.sys_usuario (
		id_usuario		VARCHAR(38) PRIMARY KEY 
	  , cd_usuario		INT IDENTITY(1,1) NOT NULL UNIQUE
	  , nm_usuario		VARCHAR(150)
	  , ds_email		VARCHAR(150) UNIQUE NOT NULL
	  , ds_senha		VARCHAR(40)
	  , cd_perfil		INT 
	  , id_token		VARCHAR(200)
	  , tp_plataforma	SMALLINT DEFAULT 0 NOT NULL
	  , ft_usuario		VARCHAR(MAX)
	);

	ALTER TABLE dbo.sys_usuario ADD FOREIGN KEY (cd_perfil)
		REFERENCES dbo.sys_perfil (cd_perfil);
END

IF OBJECT_ID (N'dbo.sys_usuario_empresa') IS NULL
BEGIN
	CREATE TABLE dbo.sys_usuario_empresa (
		id_usuario		VARCHAR(38) NOT NULL
	  , id_empresa		VARCHAR(38) NOT NULL
	  , sn_ativo		SMALLINT DEFAULT 0 NOT NULL
	  , dh_ativacao		DATETIME
	);

	ALTER TABLE dbo.sys_usuario_empresa ADD PRIMARY KEY (id_usuario, id_empresa);

	ALTER TABLE dbo.sys_usuario_empresa ADD FOREIGN KEY (id_usuario)
		REFERENCES dbo.sys_usuario (id_usuario)     
		ON DELETE CASCADE    
		ON UPDATE CASCADE;

	ALTER TABLE dbo.sys_usuario_empresa ADD FOREIGN KEY (id_empresa)
		REFERENCES dbo.sys_empresa (id_empresa)     
		ON DELETE CASCADE    
		ON UPDATE CASCADE;
END

IF OBJECT_ID (N'dbo.sys_estado') IS NULL
BEGIN
	CREATE TABLE dbo.sys_estado (
		cd_estado	INT NOT NULL PRIMARY KEY -- Código IBGE
	  , nm_estado	VARCHAR(100)
	  , sg_estado	VARCHAR(2) NOT NULL UNIQUE
	);

	DROP INDEX IF EXISTS idx_sys_estado_sigla	ON dbo.sys_estado;  
	CREATE INDEX idx_sys_estado_sigla	ON dbo.sys_estado (sg_estado ASC);
END

IF OBJECT_ID (N'dbo.sys_cidade') IS NULL
BEGIN
	CREATE TABLE dbo.sys_cidade (
		cd_cidade		INT NOT NULL PRIMARY KEY -- Código IBGE
	  , nm_cidade		VARCHAR(150)
	  , cd_estado		INT NOT NULL
	  , nr_cep_inicial	INT
	  , nr_cep_final	INT
	  , nr_ddd			INT
	);

	ALTER TABLE dbo.sys_cidade ADD FOREIGN KEY (cd_estado)
		REFERENCES dbo.sys_estado (cd_estado);

	DROP INDEX IF EXISTS idx_sys_cidade_nome	ON dbo.sys_cidade;  
	DROP INDEX IF EXISTS idx_sys_cidade_cep		ON dbo.sys_cidade;  
	CREATE INDEX idx_sys_cidade_nome	ON dbo.sys_cidade (nm_cidade ASC);
	CREATE INDEX idx_sys_cidade_cep		ON dbo.sys_cidade (nr_cep_inicial, nr_cep_final);
END

IF OBJECT_ID (N'dbo.sys_tipo_logradouro') IS NULL
BEGIN
	CREATE TABLE dbo.sys_tipo_logradouro (
		cd_tipo	INT NOT NULL PRIMARY KEY 
	  , ds_tipo	VARCHAR(150)
	  , sg_tipo	VARCHAR(5)
	);

	DROP INDEX IF EXISTS idx_sys_tipo_logradouro_desc	ON dbo.sys_tipo_logradouro;  
	DROP INDEX IF EXISTS idx_sys_tipo_logradouro_sigla	ON dbo.sys_tipo_logradouro;  
	CREATE INDEX idx_sys_tipo_logradouro_desc	ON dbo.sys_tipo_logradouro (ds_tipo ASC);
	CREATE INDEX idx_sys_tipo_logradouro_sigla		ON dbo.sys_tipo_logradouro (sg_tipo);
END

IF OBJECT_ID (N'dbo.sys_bairro') IS NULL
BEGIN
	CREATE TABLE dbo.sys_bairro (
		cd_bairro	INT IDENTITY(1,1) NOT NULL PRIMARY KEY 
	  , nm_bairro	VARCHAR(150)
	  , cd_cidade	INT NOT NULL	-- Código IBGE
	);

	ALTER TABLE dbo.sys_bairro ADD FOREIGN KEY (cd_cidade)
		REFERENCES dbo.sys_cidade (cd_cidade);
END

IF OBJECT_ID (N'dbo.sys_cep') IS NULL
BEGIN
	CREATE TABLE dbo.sys_cep (
		nr_cep			BIGINT NOT NULL PRIMARY KEY 
	  , tp_logradouro	VARCHAR(30)
	  , ds_logradouro	VARCHAR(200)
	  , ds_endereco		VARCHAR(250)	-- tp_logradouro + ' ' + ds_logradouro -> Exemplo: 'Rua' + ' ' + 'Santa Maria'
	  , nm_bairro		VARCHAR(150)
	  , nm_cidade		VARCHAR(150)
	  , sg_estado		VARCHAR(2)
	  , cd_estado		INT				-- Código IBGE
	  , cd_cidade		INT				-- Código IBGE
	  , cd_bairro		INT
	  , cd_tipo			INT
	);

	ALTER TABLE dbo.sys_cep ADD FOREIGN KEY (cd_estado)
		REFERENCES dbo.sys_estado (cd_estado)     
		ON DELETE CASCADE    
		ON UPDATE CASCADE;

	ALTER TABLE dbo.sys_cep ADD FOREIGN KEY (cd_cidade)
		REFERENCES dbo.sys_cidade (cd_cidade)     
		ON DELETE CASCADE    
		ON UPDATE CASCADE

	ALTER TABLE dbo.sys_cep ADD FOREIGN KEY (cd_bairro)
		REFERENCES dbo.sys_bairro (cd_bairro);

	ALTER TABLE dbo.sys_cep ADD FOREIGN KEY (cd_tipo)
		REFERENCES dbo.sys_tipo_logradouro (cd_tipo);

	DROP INDEX IF EXISTS idx_sys_cep_endereco	ON dbo.sys_cep;  
	DROP INDEX IF EXISTS idx_sys_cep_cidade		ON dbo.sys_cep;  
	CREATE INDEX idx_sys_cep_endereco	ON dbo.sys_cep (ds_endereco ASC);
	CREATE INDEX idx_sys_cep_cidade		ON dbo.sys_cep (nm_cidade ASC);
END

IF OBJECT_ID (N'dbo.tbl_convenio') IS NULL
BEGIN
	CREATE TABLE dbo.tbl_convenio (
		cd_convenio		INT IDENTITY(1,1) NOT NULL PRIMARY KEY 
	  , tp_convenio		SMALLINT DEFAULT 1 NOT NULL CHECK ((tp_convenio = 0) or (tp_convenio = 1)) 
	  , nm_convenio		VARCHAR(150)
	  , nm_resumido		VARCHAR(50)
	  , nr_cnpj_cpf		VARCHAR(25)
	  , nr_registro_ans	VARCHAR(10)
	  , sn_ativo		SMALLINT DEFAULT 1 NOT NULL CHECK ((sn_ativo = 0) or (sn_ativo = 1))
	);

	EXEC dbo.spDocumentarCampo N'tbl_convenio', N'cd_convenio',		N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_convenio', N'nm_convenio',		N'Nome completo (Razão Social)';
	EXEC dbo.spDocumentarCampo N'tbl_convenio', N'nm_resumido',		N'Nome resumido';
	EXEC dbo.spDocumentarCampo N'tbl_convenio', N'tp_convenio',		N'Tipo:
	0 - Pessoa física
	1 - Pessoa jurídica';
	EXEC dbo.spDocumentarCampo N'tbl_convenio', N'nr_cnpj_cpf',		N'CPF/CNPJ';
	EXEC dbo.spDocumentarCampo N'tbl_convenio', N'nr_registro_ans', N'Número do registro ANS';
	EXEC dbo.spDocumentarCampo N'tbl_convenio', N'sn_ativo',		N'Ativo:
	0 - Não
	1 - Sim';

	DROP INDEX IF EXISTS idx_tbl_convenio_tipo	ON dbo.tbl_convenio;  
	DROP INDEX IF EXISTS idx_tbl_convenio_ativo	ON dbo.tbl_convenio;  
	DROP INDEX IF EXISTS idx_tbl_convenio_nome	ON dbo.tbl_convenio;  
	CREATE INDEX idx_tbl_convenio_tipo			ON dbo.tbl_convenio (tp_convenio);
	CREATE INDEX idx_tbl_convenio_ativo			ON dbo.tbl_convenio (sn_ativo);
	CREATE INDEX idx_tbl_convenio_nome			ON dbo.tbl_convenio (nm_convenio, nm_resumido ASC);
END

IF OBJECT_ID (N'dbo.tbl_profissao') IS NULL  
BEGIN
	CREATE TABLE dbo.tbl_profissao (
		cd_profissao	INT IDENTITY(1,1) NOT NULL PRIMARY KEY
	  , ds_profissao	VARCHAR(250)
	  , nr_cbo			INT
	  , sn_ativo		SMALLINT DEFAULT 1 NOT NULL CHECK ((sn_ativo = 0) or (sn_ativo = 1))
	);
	/*
	ALTER TABLE dbo.tbl_profissao 
	  add sn_ativo SMALLINT DEFAULT 1 NOT NULL CHECK ((sn_ativo = 0) or (sn_ativo = 1));
	*/
	EXEC dbo.spDocumentarCampo N'tbl_profissao', N'cd_profissao',	N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_profissao', N'ds_profissao',	N'Descrição';
	EXEC dbo.spDocumentarCampo N'tbl_profissao', N'nr_cbo',			N'Código CBO';
	EXEC dbo.spDocumentarCampo N'tbl_profissao', N'sn_ativo',		N'Ativa:
	0 - Não
	1 - Sim';
END
GO

IF OBJECT_ID (N'dbo.tbl_paciente') IS NULL
BEGIN
	CREATE TABLE dbo.tbl_paciente (
		-- Identificação
		cd_paciente		BIGINT IDENTITY(1,1) NOT NULL PRIMARY KEY 
	  , nm_paciente		VARCHAR(150)
	  , nm_mnemonico	VARCHAR(150)
	  , dt_nascimento	DATE
	  , tp_sexo			VARCHAR(1) DEFAULT 'N' NOT NULL CHECK ((tp_sexo = 'N') or (tp_sexo = 'M') or (tp_sexo = 'F') or (tp_sexo = 'I'))
	  , nm_pai			VARCHAR(150)
	  , nm_mae			VARCHAR(150)
	  , ft_paciente		VARCHAR(MAX)
	    -- Documentos
	  , nr_cpf			VARCHAR(25)
	  , nr_rg			VARCHAR(25)
	  , ds_orgao_rg		VARCHAR(10)
	  , dt_emissao_rg	DATE
	    -- Contato
	  , nr_telefone		VARCHAR(15)
	  , nr_celular		VARCHAR(15)
	  , ds_contatos		VARCHAR(150)
	  , ds_email		VARCHAR(150)
	    -- Endereço atual
	  , nr_cep			BIGINT
	  , tp_endereco		INT
	  , ds_endereco		VARCHAR(200)
	  , nr_endereco		VARCHAR(10)
	  , ds_complemento	VARCHAR(100)
	  , nm_bairro		VARCHAR(150)
	  , cd_estado		INT				-- Código IBGE
	  , cd_cidade		INT				-- Código IBGE
	    -- Dados para atendimento médico
	  , cd_convenio		INT
	  , nr_matricula	VARCHAR(30)
	  , ds_alergias		VARCHAR(MAX)
	  , ds_observacoes	VARCHAR(MAX)
	    -- Outras informações
	  , cd_profissao	INT
	  , ds_profissao	VARCHAR(150)
	  , nm_acompanhante	VARCHAR(150)
	  , nm_indicacao	VARCHAR(150)
	    -- Auditoria básica
	  , dh_cadastro		DATETIME
	  , us_cadastro		VARCHAR(38)
	  , sn_ativo		SMALLINT DEFAULT 1 NOT NULL CHECK ((sn_ativo = 0) or (sn_ativo = 1))
	);
	/*
	ALTER TABLE dbo.tbl_paciente 
	  add ds_profissao VARCHAR(150);

	ALTER TABLE dbo.tbl_paciente 
	  add ds_contatos VARCHAR(150);

	ALTER TABLE dbo.tbl_paciente 
	    add end_logradouro	VARCHAR(150);

	ALTER TABLE dbo.tbl_paciente 
	    add end_bairro		VARCHAR(50);

	ALTER TABLE dbo.tbl_paciente 
	    add end_cidade		VARCHAR(100);

	ALTER TABLE dbo.tbl_paciente 
	    add end_estado		VARCHAR(50);
	*/
	-- Identificação
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'cd_paciente',		N'Número do Prontuário';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nm_paciente',		N'Nome completo';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nm_mnemonico',	N'Código metafônico do nome';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'dt_nascimento',	N'Data de nascimento';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'tp_sexo',			N'Sexo:
	N - Não declarado
	M - Masculino
	F - Feminino
	I - Indefinido';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nm_pai',			N'Nome do Pai';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nm_mae',			N'Nome da Mãe';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'ft_paciente',		N'Foto';
	-- Documentos
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nr_cpf',			N'CPF';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nr_rg',			N'Número do RG';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'ds_orgao_rg',		N'Orgão/UF do RG';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'dt_emissao_rg',	N'Data de emissão do RG';
	-- Contato
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nr_telefone',		N'Telefone fixo';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nr_celular',		N'Celular';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'ds_contatos',		N'Outros contatos';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'ds_email',		N'Email';
	-- Endereço atual
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nr_cep',			N'CEP';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'tp_endereco',		N'Tipo do endereço (Rua, Travessa, Rodovia, ETC.)';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'ds_endereco',		N'Descrição do endereço';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nr_endereco',		N'Número do endereço';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'ds_complemento',	N'Complemento';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nm_bairro',		N'Bairro';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'cd_estado',		N'Código IBGE do Estado';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'cd_cidade',		N'Código IBGE da Cidade/Município';
	-- Dados para atendimento médico
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'cd_convenio',		N'Convênio (Padrão : Particular)';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nr_matricula',	N'Número da matrícula do paciente no convênio';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'ds_alergias',		N'Alergias';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'ds_observacoes',	N'Observações gerais';
	-- Outras informações
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'cd_profissao',	N'Profissão';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'ds_profissao',	N'Descrição da profissão';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nm_acompanhante',	N'Nome do acmpanhante';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'nm_indicacao',	N'Nome de quem indicou';
	-- Auditoria básica
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'dh_cadastro',		N'Data/hora de cadastro';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'us_cadastro',		N'Usuário de cadastro';
	EXEC dbo.spDocumentarCampo N'tbl_paciente', N'sn_ativo',		N'Ativo:
	0 - Não
	1 - Sim';

	ALTER TABLE dbo.tbl_paciente ADD FOREIGN KEY (tp_endereco)
		REFERENCES dbo.sys_tipo_logradouro (cd_tipo);

	ALTER TABLE dbo.tbl_paciente ADD FOREIGN KEY (cd_convenio)
		REFERENCES dbo.tbl_convenio (cd_convenio);

	ALTER TABLE dbo.tbl_paciente ADD FOREIGN KEY (cd_profissao)
		REFERENCES dbo.tbl_profissao (cd_profissao);

	ALTER TABLE dbo.tbl_paciente ADD FOREIGN KEY (cd_estado)
		REFERENCES dbo.sys_estado (cd_estado);

	DROP INDEX IF EXISTS idx_tbl_paciente_nome		ON dbo.tbl_paciente;  
	DROP INDEX IF EXISTS idx_tbl_paciente_metafone	ON dbo.tbl_paciente;  
	DROP INDEX IF EXISTS idx_tbl_paciente_estado	ON dbo.tbl_paciente;  
	DROP INDEX IF EXISTS idx_tbl_paciente_cidade	ON dbo.tbl_paciente;  
	CREATE INDEX idx_tbl_paciente_nome			ON dbo.tbl_paciente (nm_paciente, nm_mnemonico);
	CREATE INDEX idx_tbl_paciente_metafone		ON dbo.tbl_paciente (nm_mnemonico);
	CREATE INDEX idx_tbl_paciente_estado		ON dbo.tbl_paciente (cd_estado);
	CREATE INDEX idx_tbl_paciente_cidade		ON dbo.tbl_paciente (cd_cidade);
END

IF OBJECT_ID (N'dbo.tbl_grupo_espec') IS NULL  
	CREATE TABLE dbo.tbl_grupo_espec (
		cd_grupo	INT IDENTITY(1,1) NOT NULL PRIMARY KEY
	  , ds_grupo	VARCHAR(50)
	  , nr_tuss		VARCHAR(10)
	);

	EXEC dbo.spDocumentarCampo N'tbl_grupo_espec', N'cd_grupo',	N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_grupo_espec', N'ds_grupo',	N'Descrição';
	EXEC dbo.spDocumentarCampo N'tbl_grupo_espec', N'nr_tuss',  N'Código TUSS';
GO

IF OBJECT_ID (N'dbo.tbl_especialidade') IS NULL  
	CREATE TABLE dbo.tbl_especialidade (
		cd_especialidade	INT IDENTITY(1,1) NOT NULL PRIMARY KEY
	  , nm_especialidade	VARCHAR(150)
	  , ds_especialidade	VARCHAR(50)
	  , cd_grupo			INT
	  , nr_tuss				VARCHAR(15)
	  , sn_ativo			SMALLINT DEFAULT 1 NOT NULL CHECK ((sn_ativo = 0) or (sn_ativo = 1))
	);

	ALTER TABLE dbo.tbl_especialidade ADD FOREIGN KEY (cd_grupo)
		REFERENCES dbo.tbl_grupo_espec (cd_grupo);

	EXEC dbo.spDocumentarCampo N'tbl_especialidade', N'cd_especialidade',	N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_especialidade', N'nm_especialidade',	N'Nome completo';
	EXEC dbo.spDocumentarCampo N'tbl_especialidade', N'ds_especialidade',   N'Descrição (Apresentação)';
	EXEC dbo.spDocumentarCampo N'tbl_especialidade', N'cd_grupo',			N'Grupo';
	EXEC dbo.spDocumentarCampo N'tbl_especialidade', N'nr_tuss',			N'Código TUSS';
	EXEC dbo.spDocumentarCampo N'tbl_especialidade', N'sn_ativo',			N'Ativo:
	0 - Não
	1 - Sim';
GO

IF OBJECT_ID (N'dbo.tbl_profissional') IS NULL  
BEGIN
	CREATE TABLE dbo.tbl_profissional (
		cd_profissional	INT IDENTITY(1,1) NOT NULL PRIMARY KEY
	  , nm_profissional	VARCHAR(150)
	  , nm_apresentacao	VARCHAR(150)
	  --, nr_conselho		VARCHAR(20)
	  --, nm_conselho		VARCHAR(20)
	  , ds_conselho		VARCHAR(40)
	  , sn_ativo		SMALLINT DEFAULT 1 NOT NULL CHECK ((sn_ativo = 0) or (sn_ativo = 1))
	);

	--ALTER TABLE dbo.tbl_profissional 
	--  add ds_conselho VARCHAR(40);

	ALTER TABLE dbo.tbl_profissional 
	  add ft_assinatura VARCHAR(MAX);

	ALTER TABLE dbo.tbl_profissional 
	  add id_usuario VARCHAR(38);

	ALTER TABLE dbo.tbl_profissional 
	  add id_empresa VARCHAR(38);

	ALTER TABLE dbo.tbl_profissional ADD FOREIGN KEY (id_usuario)
		REFERENCES dbo.sys_usuario (id_usuario);

	ALTER TABLE dbo.tbl_profissional ADD FOREIGN KEY (id_empresa)
		REFERENCES dbo.sys_empresa (id_empresa);

	EXEC dbo.spDocumentarCampo N'tbl_profissional', N'cd_profissional',	N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_profissional', N'nm_profissional',	N'Nome completo';
	EXEC dbo.spDocumentarCampo N'tbl_profissional', N'nm_apresentacao',	N'Nome de apresentação';
	--EXEC dbo.spDocumentarCampo N'tbl_profissional', N'nr_conselho',		N'Número do Conselho';
	--EXEC dbo.spDocumentarCampo N'tbl_profissional', N'nm_conselho',		N'Nome do Conselho';
	EXEC dbo.spDocumentarCampo N'tbl_profissional', N'ds_conselho',		N'Descrição do Conselho';
	EXEC dbo.spDocumentarCampo N'tbl_profissional', N'sn_ativo',		N'Ativo:
	0 - Não
	1 - Sim';
	EXEC dbo.spDocumentarCampo N'tbl_profissional', N'ft_assinatura',	N'Foto/imagem da assinatura do profissional';
	EXEC dbo.spDocumentarCampo N'tbl_profissional', N'id_usuario',		N'Usuário do profissional';
	EXEC dbo.spDocumentarCampo N'tbl_profissional', N'id_empresa',		N'Empresa';
	/*
	ALTER TABLE dbo.tbl_profissional drop column nr_conselho;
	ALTER TABLE dbo.tbl_profissional drop column nm_conselho;
	*/
END
GO

IF OBJECT_ID (N'dbo.tbl_profissional_especialidade') IS NULL  
BEGIN
	CREATE TABLE dbo.tbl_profissional_especialidade (
		cd_profissional		INT NOT NULL 
	  , cd_especialidade	INT NOT NULL 
	);

	ALTER TABLE dbo.tbl_profissional_especialidade ADD PRIMARY KEY (cd_profissional, cd_especialidade);

	ALTER TABLE dbo.tbl_profissional_especialidade ADD FOREIGN KEY (cd_profissional)
		REFERENCES dbo.tbl_profissional (cd_profissional) On Delete Cascade;

	ALTER TABLE dbo.tbl_profissional_especialidade ADD FOREIGN KEY (cd_especialidade)
		REFERENCES dbo.tbl_especialidade (cd_especialidade) On Delete Cascade;

	EXEC dbo.spDocumentarCampo N'tbl_profissional_especialidade', N'cd_profissional',	N'Código do profissional';
	EXEC dbo.spDocumentarCampo N'tbl_profissional_especialidade', N'cd_especialidade',	N'Código da especialidade';
END
GO

IF OBJECT_ID (N'dbo.tbl_tabela_cobranca') IS NULL  
BEGIN
	CREATE TABLE dbo.tbl_tabela_cobranca (
		cd_tabela			INT IDENTITY(1,1) NOT NULL PRIMARY KEY
	  , nm_tabela			VARCHAR(150)
	  , id_empresa			VARCHAR(38) NOT NULL
	  , cd_convenio			INT
	  , tp_atendimento		SMALLINT DEFAULT 0 NOT NULL CHECK ((tp_atendimento = 0) or (tp_atendimento = 1) or (tp_atendimento = 2) or (tp_atendimento = 3) or (tp_atendimento = 9))
	  , cd_especialidade	INT
	  , vl_servico			NUMERIC(18,2)
	  , sn_ativo			SMALLINT DEFAULT 1 NOT NULL CHECK ((sn_ativo = 0) or (sn_ativo = 1))
	  , dh_insercao			DATETIME
	  , us_insercao			VARCHAR(38)
	  , dh_alteracao		DATETIME
	  , us_alteracao		VARCHAR(38)
	);

	ALTER TABLE dbo.tbl_tabela_cobranca ADD FOREIGN KEY (cd_convenio)
		REFERENCES dbo.tbl_convenio (cd_convenio);

	ALTER TABLE dbo.tbl_tabela_cobranca ADD FOREIGN KEY (cd_especialidade)
		REFERENCES dbo.tbl_especialidade (cd_especialidade);

	ALTER TABLE dbo.tbl_tabela_cobranca ADD FOREIGN KEY (id_empresa)
		REFERENCES dbo.sys_empresa (id_empresa);

	EXEC dbo.spDocumentarCampo N'tbl_tabela_cobranca', N'cd_tabela',		N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_tabela_cobranca', N'nm_tabela',		N'Nome';
	EXEC dbo.spDocumentarCampo N'tbl_tabela_cobranca', N'cd_convenio',		N'Convênio';
	EXEC dbo.spDocumentarCampo N'tbl_tabela_cobranca', N'tp_atendimento',	N'Tipo de atendimento:
	0 - 
	1 - Consuta
	2 - Retorno
	3 - Cortesia
	9 - ';
	EXEC dbo.spDocumentarCampo N'tbl_tabela_cobranca', N'cd_especialidade',	N'Especialidade';
	EXEC dbo.spDocumentarCampo N'tbl_tabela_cobranca', N'vl_servico',		N'Valor do Serviço (R$)';
	EXEC dbo.spDocumentarCampo N'tbl_tabela_cobranca', N'dh_insercao',		N'Data/hora da inserção';
	EXEC dbo.spDocumentarCampo N'tbl_tabela_cobranca', N'us_insercao',		N'Usuário da inserção';
	EXEC dbo.spDocumentarCampo N'tbl_tabela_cobranca', N'dh_alteracao',		N'Data/hora da última alteração';
	EXEC dbo.spDocumentarCampo N'tbl_tabela_cobranca', N'us_alteracao',		N'Usuário da última alteração';
	EXEC dbo.spDocumentarCampo N'tbl_tabela_cobranca', N'sn_ativo',			N'Ativo:
	0 - Não
	1 - Sim';
END
GO

CREATE OR ALTER FUNCTION dbo.ufnGetTabelaServico(@Empresa VARCHAR(38), @Convenio INT, @TipoAtendimento INT, @Especialidade INT)
RETURNS INT
AS   
BEGIN  
  DECLARE @codigo INT;  
  Set @codigo = NULL;

  Select TOP 1
    @codigo = cd_tabela
  from dbo.tbl_tabela_cobranca v
  where (v.id_empresa	= @Empresa)
    and (v.sn_ativo		= 1)
	and (v.cd_convenio		= @Convenio)
	and (v.tp_atendimento	= @TipoAtendimento)
	and (v.cd_especialidade	= @Especialidade)
  order by
	v.cd_tabela DESC;

  RETURN @codigo;  
END;
GO

CREATE OR ALTER FUNCTION dbo.ufnGetValorServico(@Empresa VARCHAR(38), @Convenio INT, @TipoAtendimento INT, @Especialidade INT)
RETURNS numeric(18,2)
AS   
BEGIN  
  DECLARE @valor numeric(18,2);  
  Set @valor = 0.0;

  Select TOP 1
    @valor = coalesce(v.vl_servico, 0.0)
  from dbo.tbl_tabela_cobranca v
  where (v.id_empresa	= @Empresa)
    and (v.sn_ativo		= 1)
	and (v.cd_convenio		= @Convenio)
	and (v.tp_atendimento	= @TipoAtendimento)
	and (v.cd_especialidade	= @Especialidade)
  order by
	v.cd_tabela DESC;

  RETURN @valor;  
END;
GO

CREATE OR ALTER PROCEDURE dbo.getTabelaValor
	@Empresa			VARCHAR(38)
  , @Convenio			INT
  , @Especialidade		INT
  , @TipoAtendimento	INT
AS
BEGIN
  if (@Empresa <> '')
  Begin
	Select
	    v.cd_tabela
	  , v.nm_tabela
	  , coalesce(v.vl_servico, 0.0) as vl_servico
	from dbo.tbl_tabela_cobranca v
	where (v.id_empresa = @Empresa) 
	  and (coalesce(v.cd_convenio, 0)	   = coalesce(@Convenio, 0))
	  and (coalesce(v.cd_especialidade, 0) = coalesce(@Especialidade, 0))
	  and ((coalesce(v.tp_atendimento, 0)  = coalesce(@TipoAtendimento, 0)) or (coalesce(@TipoAtendimento, 0) = 0))
	  and (v.sn_ativo = 1)
	Order by
	  v.cd_tabela DESC;
  End
END
GO

CREATE OR ALTER PROCEDURE dbo.getTabelaValor_v2
	@Tabela		Int
  , @Empresa	Varchar(38)
AS
BEGIN
  if ((@Tabela > 0) and (@Empresa <> ''))
  Begin
	Select
	    v.cd_tabela
	  , v.nm_tabela
	  , coalesce(v.cd_convenio, 0)		as cd_convenio
	  , coalesce(v.cd_especialidade, 0) as cd_especialidade
	  , coalesce(tp_atendimento, 0)		as tp_atendimento
	  , coalesce(v.vl_servico, 0.0)     as vl_servico
	from dbo.tbl_tabela_cobranca v
	where (v.cd_tabela  = @Tabela)
	  and (v.id_empresa = @Empresa) 
	  and (v.sn_ativo = 1)
	Order by
	  v.cd_tabela DESC;
  End
END
GO

IF OBJECT_ID (N'dbo.tbl_configurar_agenda') IS NULL  
	CREATE TABLE dbo.tbl_configurar_agenda (
		cd_agenda			INT IDENTITY(1,1) NOT NULL PRIMARY KEY
	  , nm_agenda			VARCHAR(50)
	  , ds_observacoes		VARCHAR(250)
	  , id_empresa			VARCHAR(38)
	  , cd_especialidade	INT 
	  , cd_profissional		INT 
	  , dt_inicial			DATE
	  , dt_final			DATE
	  , hr_divisao_agenda	CHAR(5) DEFAULT '00:15' NOT NULL -- Formato "hh:mm" (24h)
	  , sn_ativo			SMALLINT DEFAULT 1 NOT NULL CHECK ((sn_ativo = 0)   or (sn_ativo = 1))
	  -- Dias de funcionamento
	  , sn_domingo			SMALLINT DEFAULT 0 NOT NULL CHECK ((sn_domingo = 0) or (sn_domingo = 1))
	  , sn_segunda			SMALLINT DEFAULT 0 NOT NULL CHECK ((sn_segunda = 0) or (sn_segunda = 1))
	  , sn_terca			SMALLINT DEFAULT 0 NOT NULL CHECK ((sn_terca = 0)   or (sn_terca = 1))
	  , sn_quarta			SMALLINT DEFAULT 0 NOT NULL CHECK ((sn_quarta = 0)  or (sn_quarta = 1))
	  , sn_quinta			SMALLINT DEFAULT 0 NOT NULL CHECK ((sn_quinta = 0)  or (sn_quinta = 1))
	  , sn_sexta			SMALLINT DEFAULT 0 NOT NULL CHECK ((sn_sexta = 0)   or (sn_sexta = 1))
	  , sn_sabado			SMALLINT DEFAULT 0 NOT NULL CHECK ((sn_sabado = 0)  or (sn_sabado = 1))
	  -- Domingo 
	  , hr_dom_ini_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_dom_fim_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_dom_ini_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_dom_fim_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  -- Segunda
	  , hr_seg_ini_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_seg_fim_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_seg_ini_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_seg_fim_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  -- Terça
	  , hr_ter_ini_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_ter_fim_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_ter_ini_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_ter_fim_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  -- Quarta
	  , hr_qua_ini_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_qua_fim_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_qua_ini_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_qua_fim_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  -- Quitnta
	  , hr_qui_ini_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_qui_fim_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_qui_ini_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_qui_fim_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  -- Sexta
	  , hr_sex_ini_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_sex_fim_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_sex_ini_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_sex_fim_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  -- Sábado
	  , hr_sab_ini_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_sab_fim_manha	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_sab_ini_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	  , hr_sab_fim_tarde	CHAR(5) -- Formato "hh:mm" (24h)
	);

	ALTER TABLE dbo.tbl_configurar_agenda ADD FOREIGN KEY (id_empresa)
		REFERENCES dbo.sys_empresa (id_empresa);

	ALTER TABLE dbo.tbl_configurar_agenda ADD FOREIGN KEY (cd_especialidade)
		REFERENCES dbo.tbl_especialidade (cd_especialidade);

	ALTER TABLE dbo.tbl_configurar_agenda ADD FOREIGN KEY (cd_profissional)
		REFERENCES dbo.tbl_profissional (cd_profissional);

	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'cd_agenda',			N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'nm_agenda',			N'Nome da agenda';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'ds_observacoes',		N'Observações';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'id_empresa',			N'Empresa';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'cd_especialidade',	N'Especialidade';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'cd_profissional',	N'Profissional';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'dt_inicial',			N'Data inicial da vigência';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'dt_final',			N'Data final da vigência';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'hr_divisao_agenda',	N'Tempo de divisão dos horários';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'sn_domingo',			N'Atendimento no Domingo:
	0 - Não
	1 - Sim';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'sn_domingo',			N'Atendimento no Domingo:
	0 - Não
	1 - Sim';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'sn_segunda',			N'Atendimento no Segunda:
	0 - Não
	1 - Sim';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'sn_terca',			N'Atendimento no Terça:
	0 - Não
	1 - Sim';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'sn_quarta',			N'Atendimento no Quarta:
	0 - Não
	1 - Sim';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'sn_quinta',			N'Atendimento no Quinta:
	0 - Não
	1 - Sim';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'sn_sexta',			N'Atendimento no Sexta:
	0 - Não
	1 - Sim';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'sn_sabado',			N'Atendimento no Sábado:
	0 - Não
	1 - Sim';
	EXEC dbo.spDocumentarCampo N'tbl_configurar_agenda', N'sn_ativo',			N'Ativo:
	0 - Não
	1 - Sim';
GO

-- Exec dbo.getHorarios N'08:00', N'12:00', N'00:15';
CREATE OR ALTER PROCEDURE dbo.getHorarios
	@HoraIni	char(5)
  , @HoraFim	char(5)
  , @Tempo		char(5)
AS
BEGIN
  Declare @HoraInicio	Time;
  Declare @HoraFinal	Time;
  Declare @Intervalo	Int;

  if ((@HoraIni <> '') and (@HoraFim <> '') and (@Tempo <> ''))
  Begin
    Set @Intervalo  = (cast(SUBSTRING(@Tempo, 1, 2) as Int) * 60) + cast(SUBSTRING(@Tempo, 4, 2) as Int);
	Set @HoraInicio = cast(@HoraIni as Time);
	Set @HoraFinal  = cast(@HoraFim as Time);

	if (@Intervalo = 0) 
	  Set @Intervalo = 5; -- Quantidade mínima de minutos

	with TEMP_HORARIOS as (
		Select @HoraInicio as Horario
    
		union all
    
		Select DATEADD(MINUTE, @Intervalo, Horario)
		from TEMP_HORARIOS
		where DATEADD(MINUTE, @Intervalo, Horario) <= @HoraFinal
	)
	Select * from TEMP_HORARIOS;
  End
END
GO

IF OBJECT_ID (N'dbo.tbl_agenda') IS NULL
BEGIN
	CREATE TABLE dbo.tbl_agenda (
		id_agenda			VARCHAR(38) PRIMARY KEY 
	  , cd_agenda			BIGINT IDENTITY(1,1) NOT NULL UNIQUE
	  , cd_configuracao		INT 
	  , dt_agenda			DATE
	  , hr_agenda			TIME
	  , st_agenda			SMALLINT DEFAULT 0 NOT NULL CHECK ((st_agenda = 0) or (st_agenda = 1) or (st_agenda = 2) or (st_agenda = 3) or (st_agenda = 4) or (st_agenda = 9))
	  , tp_atendimento		SMALLINT DEFAULT 0 NOT NULL CHECK ((tp_atendimento = 0) or (tp_atendimento = 1) or (tp_atendimento = 2) or (tp_atendimento = 3) or (tp_atendimento = 9))
	  , cd_paciente			BIGINT 
	  , nm_paciente			VARCHAR(150)
	  , nr_celular			VARCHAR(15)
	  , nr_telefone			VARCHAR(15)
	  , ds_email			VARCHAR(150)
	  , ds_observacao		VARCHAR(250)
	  , cd_convenio			INT
	  , cd_especialidade	INT
	  , cd_profissional		INT
	  , cd_tabela			INT
	  , cd_servico			INT
	  , vl_servico			NUMERIC(18,2)
	  , dh_insercao			DATETIME
	  , us_insercao			VARCHAR(38)
	  , dh_alteracao		DATETIME
	  , us_alteracao		VARCHAR(38)
	  , id_empresa			VARCHAR(38) NOT NULL
	  , id_atendimento		VARCHAR(38)
	  , sn_avulso			SMALLINT DEFAULT 0 NOT NULL CHECK ((sn_avulso = 0) or (sn_avulso = 1))
	);

	ALTER TABLE dbo.tbl_agenda ADD FOREIGN KEY (cd_configuracao)
		REFERENCES dbo.tbl_configurar_agenda (cd_agenda);

	ALTER TABLE dbo.tbl_agenda ADD FOREIGN KEY (cd_paciente)
		REFERENCES dbo.tbl_paciente (cd_paciente);

	ALTER TABLE dbo.tbl_agenda ADD FOREIGN KEY (cd_convenio)
		REFERENCES dbo.tbl_convenio (cd_convenio);

	ALTER TABLE dbo.tbl_agenda ADD FOREIGN KEY (cd_especialidade)
		REFERENCES dbo.tbl_especialidade (cd_especialidade);

	ALTER TABLE dbo.tbl_agenda ADD FOREIGN KEY (cd_tabela)
		REFERENCES dbo.tbl_tabela_cobranca (cd_tabela);

	ALTER TABLE dbo.tbl_agenda ADD FOREIGN KEY (cd_profissional)
		REFERENCES dbo.tbl_profissional (cd_profissional);

	ALTER TABLE dbo.tbl_agenda ADD FOREIGN KEY (id_empresa)
		REFERENCES dbo.sys_empresa (id_empresa);

	ALTER TABLE dbo.tbl_agenda 
	  add dh_confirmacao DATETIME;

	ALTER TABLE dbo.tbl_agenda 
	  add dh_chamada DATETIME;

	ALTER TABLE dbo.tbl_agenda 
	  add dh_atendimento DATETIME;

	DROP INDEX IF EXISTS idx_tbl_agenda_data_hora	ON dbo.tbl_agenda;  
	DROP INDEX IF EXISTS idx_tbl_agenda_situacao	ON dbo.tbl_agenda;  
	CREATE INDEX idx_tbl_agenda_data_hora			ON dbo.tbl_agenda (dt_agenda, hr_agenda);
	CREATE INDEX idx_tbl_agenda_situacao			ON dbo.tbl_agenda (st_agenda);
	CREATE INDEX idx_tbl_agenda_hoje				ON dbo.tbl_agenda (id_empresa, dt_agenda);

	EXEC dbo.spDocumentarCampo N'tbl_agenda', N'id_agenda',			N'ID';
	EXEC dbo.spDocumentarCampo N'tbl_agenda', N'cd_agenda',			N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_agenda', N'cd_configuracao',	N'Configurações da agenda';
	EXEC dbo.spDocumentarCampo N'tbl_agenda', N'dt_agenda',			N'Data';
	EXEC dbo.spDocumentarCampo N'tbl_agenda', N'hr_agenda',			N'Hora';
	EXEC dbo.spDocumentarCampo N'tbl_agenda', N'cd_tabela',			N'Tabela de Cobrança (Preço)';
	EXEC dbo.spDocumentarCampo N'tbl_agenda', N'cd_servico',		N'Código do Serviço:
	- Este dado será o mesmo do campo CD_TABELA, pois, futuramente a Tabela de Cobrança se transformará em
	uma tabela de serviços com preços diferenciados para cada tipo de atendimento e convênio e esta tabela
	estará atrelada a uma tabela master.
	- Com o agendamento avulso o campo CD_CONFIGURACAO estará nulo (NULL).';
	EXEC dbo.spDocumentarCampo N'tbl_agenda', N'st_agenda',			N'Situação do horário:
	0 - Livre
	1 - Agendado
	2 - Confirmado
	3 - Atendido
	4 - Cancelado
	9 - Bloqueado';
	EXEC dbo.spDocumentarCampo N'tbl_agenda', N'tp_atendimento',	N'Tipo do atendimento:
	0 - Sem atendimento
	1 - Consulta
	2 - Retorno
	3 - Cortesia
	9 - Representante';
	EXEC dbo.spDocumentarCampo N'tbl_agenda', N'sn_avulso',			N'Agendamento avulso:
	0 - Não
	1 - Sim';
	EXEC dbo.spDocumentarCampo N'tbl_agenda', N'id_atendimento',	N'Atendimento gerado a partir do agendamento confirmado e atendido pelo profissional médico';
END
GO

CREATE OR ALTER VIEW dbo.vw_situacao_agenda
AS
	Select 0 as cd_situacao, 'Livre' as ds_situacao union
	Select 1, 'Agendado'	union
	Select 2, 'Confirmado'	union
	Select 3, 'Atendido'	union
	Select 4, 'Cancelado'	union
	Select 9, 'Bloqueado'
GO

CREATE OR ALTER VIEW dbo.vw_tipo_atendimento
AS
	Select 0 as cd_tipo, '...' as ds_tipo union
	Select 1, 'Consulta'	union
	Select 2, 'Retorno'		union
	Select 3, 'Costesia'	union
	Select 9, 'Representante'
GO

-- Exec dbo.setHorariosAgenda N'08/05/2019', N'{300CE361-27DC-4FB1-85D8-63A2F746CD11}', 0, 0;
CREATE OR ALTER PROCEDURE dbo.setHorariosAgenda
	@DataStr		Varchar(10)
  , @Empresa		Varchar(38)
  , @Especialidade	Int
  , @Profissional	Int
AS
BEGIN
  Declare @data Date;
  Declare @Horarios TABLE (Horario Time);
  
  Declare @Configuracao	Int;
  Declare @DiaSemana	Int;
  Declare @Liberado		Int;
  Declare @Intervalo	Char(5);
  Declare @HoraIniManha	Char(5);
  Declare @HoraFimManha	Char(5);
  Declare @HoraIniTarde	Char(5);
  Declare @HoraFimTarde	Char(5);

  if ((coalesce(@DataStr, '') <> '') and (coalesce(@Empresa, '') <> ''))
  Begin
    Set @data = convert(date, @DataStr, 103);

	Select TOP 1
	    @Configuracao = c.cd_agenda
	  , @DiaSemana = DATEPART(weekday, @data)
	  , @Intervalo = c.hr_divisao_agenda

	  , @Liberado = 
	    Case DATEPART(weekday, @data) 
		  when 1 then c.sn_domingo
		  when 2 then c.sn_segunda
		  when 3 then c.sn_terca
		  when 4 then c.sn_quarta
		  when 5 then c.sn_quinta
		  when 6 then c.sn_sexta
		  when 7 then c.sn_sabado
		end 

	  , @HoraIniManha =
	    Case DATEPART(weekday, @data) 
		  when 1 then c.hr_dom_ini_manha
		  when 2 then c.hr_seg_ini_manha
		  when 3 then c.hr_ter_ini_manha
		  when 4 then c.hr_qua_ini_manha
		  when 5 then c.hr_qui_ini_manha
		  when 6 then c.hr_sex_ini_manha
		  when 7 then c.hr_sab_ini_manha
		end 

	  , @HoraFimManha =
	    Case DATEPART(weekday, @data) 
		  when 1 then c.hr_dom_fim_manha
		  when 2 then c.hr_seg_fim_manha
		  when 3 then c.hr_ter_fim_manha
		  when 4 then c.hr_qua_fim_manha
		  when 5 then c.hr_qui_fim_manha
		  when 6 then c.hr_sex_fim_manha
		  when 7 then c.hr_sab_fim_manha
		end 

	  , @HoraIniTarde =
	    Case DATEPART(weekday, @data) 
		  when 1 then c.hr_dom_ini_tarde
		  when 2 then c.hr_seg_ini_tarde
		  when 3 then c.hr_ter_ini_tarde
		  when 4 then c.hr_qua_ini_tarde
		  when 5 then c.hr_qui_ini_tarde
		  when 6 then c.hr_sex_ini_tarde
		  when 7 then c.hr_sab_ini_tarde
		end 

	  , @HoraFimTarde =
	    Case DATEPART(weekday, @data) 
		  when 1 then c.hr_dom_fim_tarde
		  when 2 then c.hr_seg_fim_tarde
		  when 3 then c.hr_ter_fim_tarde
		  when 4 then c.hr_qua_fim_tarde
		  when 5 then c.hr_qui_fim_tarde
		  when 6 then c.hr_sex_fim_tarde
		  when 7 then c.hr_sab_fim_tarde
		end 
	from dbo.tbl_configurar_agenda c
	where (c.sn_ativo = 1)
	  and (@data between c.dt_inicial and c.dt_final)
	  and (coalesce(c.cd_especialidade, 0) = @Especialidade)
	  and (coalesce(c.cd_profissional, 0)  = @Profissional)
	order by
		c.cd_agenda DESC;

	If (@Liberado = 1) 
	Begin
	  if (@HoraIniManha is not null) and (@HoraFimManha is not null)
		Insert Into @Horarios Exec dbo.getHorarios @HoraIniManha, @HoraFimManha, @Intervalo 
		
	  if (@HoraIniTarde is not null) and (@HoraFimTarde is not null)
		Insert Into @Horarios Exec dbo.getHorarios @HoraIniTarde, @HoraFimTarde, @Intervalo;
	End

	Insert Into dbo.tbl_agenda (
	    id_agenda
	  , cd_configuracao
	  , id_empresa
	  , cd_especialidade
	  , cd_profissional
	  , dt_agenda
	  , hr_agenda
	)  
	Select 
	    dbo.ufnGetGuidID() 
	  , @Configuracao  
	  , @Empresa       
	  , Case when @Especialidade = 0 then NULL else @Especialidade end
	  , Case when @Profissional  = 0 then NULL else @Profissional end
	  , @Data		   
	  , h.Horario	   
	from @Horarios h
	  left join dbo.tbl_agenda a on (
	        a.id_empresa      = @Empresa 
		and a.cd_configuracao = @Configuracao 
		and a.dt_agenda = @Data
		and a.hr_agenda = h.Horario
		and a.sn_avulso = 0          -- Atendimentos marcados normalmente, obedecendo a agenda
		and (a.st_agenda not in (4)) -- 4. Cancelado
	  )
	where (a.cd_agenda is null);
  End
END
GO

CREATE OR ALTER PROCEDURE dbo.getAgendaQtdeAtendimento
	@Ano		Int
  , @Mes		Int
  , @Empresa	Varchar(38)
AS
BEGIN
  if ((@Ano > 0) and (@Mes > 0) and (@Empresa <> ''))
  Begin
	Select 
		day(a.dt_agenda) as nr_dia
	  , convert(varchar(12), a.dt_agenda, 103) as dt_agenda
	  , sum(a.st_agenda) as nr_agendamentos
	from dbo.tbl_agenda a
	where (a.id_empresa       = @Empresa)
	  and (year(a.dt_agenda)  = @Ano)
	  and (month(a.dt_agenda) = @Mes)
	group by
		day(a.dt_agenda)
	  , convert(varchar(12), a.dt_agenda, 103)
	order by
		day(a.dt_agenda)
  End
END
GO

CREATE OR ALTER PROCEDURE dbo.getUltimoAtendimento
	@Agenda		Varchar(38)
  , @Empresa	Varchar(38)
  , @Data		Varchar(10)
  , @Paciente	Int
AS
BEGIN
	Declare @cd_agenda Bigint;

	Select
	  @cd_agenda = max(a.cd_agenda)
	from dbo.tbl_agenda a
	where (a.st_agenda in (1, 2, 3)) -- 1. Agendado, 2. Confirmado, 3. Atendido
	  and (a.id_empresa  = @Empresa)
	  and (a.id_agenda  <> @Agenda)
	  and (a.cd_paciente = @Paciente)
	  and (a.dt_agenda   < convert(date, @Data, 103));

	Select 
		a.id_agenda
	  , a.cd_agenda
	  , a.dt_agenda
	  , a.hr_agenda
	  , coalesce(a.cd_tabela, 0)		as cd_tabela
	  , coalesce(a.cd_servico, 0)		as cd_servico
	  , coalesce(a.cd_especialidade, 0)	as cd_especialidade
	  , coalesce(a.vl_servico, 0.0)		as vl_servico
	  , convert(varchar(12), a.dt_agenda, 103) as data
	  , convert(varchar(12), a.hr_agenda, 108) as hora
	from dbo.tbl_agenda a
	where (a.cd_agenda = @cd_agenda);
END
GO

CREATE OR ALTER PROCEDURE dbo.getAgendaPaciente
	@Agenda		Varchar(38)
  , @Empresa	Varchar(38)
AS
BEGIN
	Select 
		coalesce(a.cd_paciente, 0) as prontuario
	  , coalesce(p.nm_paciente, a.nm_paciente)     as paciente
	  , convert(varchar(12), p.dt_nascimento, 103) as nascimento
	  , left(convert(varchar(12), p.dt_nascimento, 120), 10)     as dt_nasc
	  , left(convert(varchar(12), getdate(), 120), 10)           as dt_hoje
	  , coalesce(nullif(trim(a.nr_celular), ''), p.nr_celular)   as celular
	  , coalesce(nullif(trim(a.nr_telefone), ''), p.nr_telefone) as telefone
	  , coalesce(nullif(trim(a.ds_email), ''), p.ds_email)       as email
	  , convert(varchar(12), getdate(), 103)   as hoje_agenda
	  , convert(varchar(12), a.dt_agenda, 103) as data_agenda
	  , convert(varchar(12), a.hr_agenda, 108) as hora_agenda
	  , convert(varchar(12), coalesce(a.dh_alteracao, a.dh_atendimento), 103) as data_atendimento
	  , convert(varchar(12), coalesce(a.dh_alteracao, a.dh_atendimento), 108) as hora_atendimento
	  , t.ds_tipo     as tipo
	  , s.ds_situacao as situacao
	  , c.nm_resumido as convenio
	  , v.nm_tabela   as servico
	  , e.ds_especialidade as especialidade
	  , coalesce(nullif(trim(m.nm_apresentacao), ''), m.nm_profissional)  as profissional
	  , u.nm_usuario as atendente
	  , a.*
	  , p.*
	from dbo.tbl_agenda a
	  left join dbo.tbl_paciente p on (p.cd_paciente = a.cd_paciente)
	  left join dbo.vw_tipo_atendimento t on (t.cd_tipo = a.tp_atendimento)
	  left join dbo.vw_situacao_agenda s on (s.cd_situacao = a.st_agenda)
	  left join dbo.tbl_convenio c on (c.cd_convenio = a.cd_convenio)
	  left join dbo.tbl_tabela_cobranca v on (v.cd_tabela = a.cd_tabela)
	  left join dbo.tbl_especialidade e on (e.cd_especialidade = a.cd_especialidade)
	  left join dbo.tbl_profissional m on (m.cd_profissional = a.cd_profissional)
	  left join dbo.sys_usuario u on (u.id_usuario = a.us_alteracao)

	where (a.id_agenda  = @Agenda)
	  and (a.id_empresa = @Empresa);
END
GO

CREATE OR ALTER PROCEDURE dbo.getDisponibilidadeAgenda
	@Empresa	Varchar(38)
  , @Data		Varchar(10)
AS
BEGIN
	Select 
		x.* 
	  , x.qt_horarios - (x.qt_agendados + x.qt_confirmados + x.qt_atendidos + x.qt_bloqueados) as qt_disponivel
	  , (x.qt_agendados   + x.qt_confirmados + x.qt_atendidos) as qt_agendamentos
	  , (x.qt_confirmados + x.qt_atendidos) as qt_confirmacoes
	from (
		Select
			a.dt_agenda
		  , sum(case when a.st_agenda = 0 then 1 else 0 end) as qt_horarios
		  , sum(case when a.st_agenda = 1 then 1 else 0 end) as qt_agendados
		  , sum(case when a.st_agenda = 2 then 1 else 0 end) as qt_confirmados
		  , sum(case when a.st_agenda = 3 then 1 else 0 end) as qt_atendidos
		  , sum(case when a.st_agenda = 4 then 1 else 0 end) as qt_cancelados
		  , sum(case when a.st_agenda = 9 then 1 else 0 end) as qt_bloqueados
		from dbo.tbl_agenda a

		where (a.id_empresa = @Empresa)
		  and (a.dt_agenda  = convert(date, @Data, 103))

		group by
			a.dt_agenda
	) x
	order by
	  x.dt_agenda;
END
GO

CREATE OR ALTER PROCEDURE dbo.getHistoricoAtendimento
	@Agenda		Varchar(38)
  , @Empresa	Varchar(38)
  , @Data		Varchar(10)
  , @Paciente	Int
AS
BEGIN
	Select 
		coalesce(a.cd_paciente, 0) as prontuario
	  , coalesce(p.nm_paciente, a.nm_paciente)     as paciente
	  , convert(varchar(12), p.dt_nascimento, 103) as nascimento
	  , left(convert(varchar(12), p.dt_nascimento, 120), 10)     as dt_nasc
	  , left(convert(varchar(12), getdate(), 120), 10)           as dt_hoje
	  , coalesce(nullif(trim(a.nr_celular), ''), p.nr_celular)   as celular
	  , coalesce(nullif(trim(a.nr_telefone), ''), p.nr_telefone) as telefone
	  , coalesce(nullif(trim(a.ds_email), ''), p.ds_email)       as email
	  , convert(varchar(12), getdate(), 103)   as hoje_agenda
	  , convert(varchar(12), a.dt_agenda, 103) as data_agenda
	  , convert(varchar(12), a.hr_agenda, 108) as hora_agenda
	  , convert(varchar(12), coalesce(a.dh_alteracao, a.dh_atendimento), 103) as data_atendimento
	  , convert(varchar(12), coalesce(a.dh_alteracao, a.dh_atendimento), 108) as hora_atendimento
	  , t.ds_tipo     as tipo
	  , s.ds_situacao as situacao
	  , c.nm_resumido as convenio
	  , v.nm_tabela   as servico
	  , e.ds_especialidade as especialidade
	  , coalesce(nullif(trim(m.nm_apresentacao), ''), m.nm_profissional)  as profissional
	  , u.nm_usuario as atendente
	  , a.*
	from dbo.tbl_agenda a
	  left join dbo.tbl_paciente p on (p.cd_paciente = a.cd_paciente)
	  left join dbo.vw_tipo_atendimento t on (t.cd_tipo = a.tp_atendimento)
	  left join dbo.vw_situacao_agenda s on (s.cd_situacao = a.st_agenda)
	  left join dbo.tbl_convenio c on (c.cd_convenio = a.cd_convenio)
	  left join dbo.tbl_tabela_cobranca v on (v.cd_tabela = a.cd_tabela)
	  left join dbo.tbl_especialidade e on (e.cd_especialidade = a.cd_especialidade)
	  left join dbo.tbl_profissional m on (m.cd_profissional = a.cd_profissional)
	  left join dbo.sys_usuario u on (u.id_usuario = a.us_alteracao)
	where (a.id_empresa  = @Empresa)
	  and (a.id_agenda  <> @Agenda)
	  and (a.cd_paciente = @Paciente)
	  and (a.dt_agenda   < convert(date, @Data, 103))
	order by
		a.dt_agenda desc
	  , a.hr_agenda desc;
END
GO

IF OBJECT_ID (N'dbo.tbl_exame') IS NULL  
BEGIN
	CREATE TABLE dbo.tbl_exame (
		id_exame	VARCHAR(38) NOT NULL PRIMARY KEY 
	  , cd_exame	INT NOT NULL
	  , nm_exame	VARCHAR(250)
	  , sg_exame	VARCHAR(25)
	  , un_exame	VARCHAR(25)
	  , sn_ativo	SMALLINT DEFAULT 1 NOT NULL CHECK ((sn_ativo = 0) or (sn_ativo = 1))
	  , id_empresa	VARCHAR(38) NOT NULL
	);

	ALTER TABLE dbo.tbl_exame ADD FOREIGN KEY (id_empresa)
		REFERENCES dbo.sys_empresa (id_empresa);

	ALTER TABLE dbo.tbl_exame 
	  add constraint uk_cd_exame Unique(cd_exame, id_empresa);

	DROP INDEX IF EXISTS idx_tbl_exame_ativo	ON dbo.tbl_exame;  
	DROP INDEX IF EXISTS idx_tbl_exame_nome		ON dbo.tbl_exame;  
	CREATE INDEX idx_tbl_exame_ativo			ON dbo.tbl_exame (sn_ativo);
	CREATE INDEX idx_tbl_exame_nome				ON dbo.tbl_exame (nm_exame, sg_exame);

	EXEC dbo.spDocumentarCampo N'tbl_exame', N'id_exame',	N'ID';
	EXEC dbo.spDocumentarCampo N'tbl_exame', N'cd_exame',	N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_exame', N'nm_exame',	N'Nome';
	EXEC dbo.spDocumentarCampo N'tbl_exame', N'sg_exame',	N'Sigla';
	EXEC dbo.spDocumentarCampo N'tbl_exame', N'un_exame',	N'Unidade de medida';
	EXEC dbo.spDocumentarCampo N'tbl_exame', N'sn_ativo',	N'Ativa:
	0 - Não
	1 - Sim';
END
GO

IF OBJECT_ID (N'dbo.tbl_evolucao') IS NULL  
BEGIN
	CREATE TABLE dbo.tbl_evolucao (
		id_evolucao	VARCHAR(38) NOT NULL PRIMARY KEY 
	  , cd_evolucao	INT NOT NULL
	  , ds_evolucao	VARCHAR(250)
	  , un_evolucao	VARCHAR(25)
	  , sn_ativo	SMALLINT DEFAULT 1 NOT NULL CHECK ((sn_ativo = 0) or (sn_ativo = 1))
	  , id_empresa	VARCHAR(38) NOT NULL
	);

	ALTER TABLE dbo.tbl_evolucao ADD FOREIGN KEY (id_empresa)
		REFERENCES dbo.sys_empresa (id_empresa);

	ALTER TABLE dbo.tbl_evolucao 
	  add constraint uk_cd_evolucao Unique(cd_evolucao, id_empresa);

	DROP INDEX IF EXISTS idx_tbl_evolucao_ativo		ON dbo.tbl_evolucao;
	DROP INDEX IF EXISTS idx_tbl_evolucao_descricao	ON dbo.tbl_evolucao;
	CREATE INDEX idx_tbl_evolucao_ativo				ON dbo.tbl_evolucao (sn_ativo);
	CREATE INDEX idx_tbl_evolucao_descricao			ON dbo.tbl_evolucao (ds_evolucao);

	EXEC dbo.spDocumentarCampo N'tbl_evolucao', N'id_evolucao',	N'ID';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao', N'cd_evolucao',	N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao', N'ds_evolucao',	N'Descrição';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao', N'un_evolucao',	N'Unidade de medida';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao', N'sn_ativo',	N'Ativo:
	0 - Não
	1 - Sim';
END
GO

IF OBJECT_ID (N'dbo.ufnGetNextCodigoExame', N'FN') IS NOT NULL  
    DROP FUNCTION dbo.ufnGetNextCodigoExame;  
GO  
CREATE OR ALTER FUNCTION dbo.ufnGetNextCodigoExame(@Empresa VARCHAR(38))
RETURNS Int
AS   
BEGIN  
  DECLARE @retorno Int;  

  SELECT 
    @retorno = max(e.cd_exame) 
  from dbo.tbl_exame e 
  where e.id_empresa = @Empresa;

  RETURN (coalesce(@retorno, 0) + 1);  
END;
GO

IF OBJECT_ID (N'dbo.ufnGetNextCodigoEvolucao', N'FN') IS NOT NULL  
    DROP FUNCTION dbo.ufnGetNextCodigoEvolucao;  
GO  
CREATE OR ALTER FUNCTION dbo.ufnGetNextCodigoEvolucao(@Empresa VARCHAR(38))
RETURNS Int
AS   
BEGIN  
  DECLARE @retorno Int;  

  SELECT 
    @retorno = max(e.cd_evolucao) 
  from dbo.tbl_evolucao e 
  where e.id_empresa = @Empresa;

  RETURN (coalesce(@retorno, 0) + 1);  
END;
GO

USE SystemGCM
GO
IF OBJECT_ID (N'dbo.tbl_atendimento') IS NULL  
BEGIN
	CREATE TABLE dbo.tbl_atendimento (
		id_atendimento		VARCHAR(38) NOT NULL PRIMARY KEY 
	  , cd_atendimento		BIGINT IDENTITY(1,1) NOT NULL UNIQUE
	  , dt_atendimento		DATE
	  , hr_atendimento		TIME
	  , cd_paciente			BIGINT NOT NULL
	  , cd_convenio			INT
	  , cd_especialidade	INT
	  , cd_profissional		INT
	  , ds_historia			VARCHAR(MAX)
	  , ds_prescricao		VARCHAR(MAX)
	  , st_atendimento		SMALLINT DEFAULT 1 NOT NULL CHECK (st_atendimento between 0 and 5)
	  , dh_atualizacao		DATETIME
	  , us_atualizacao		VARCHAR(38)
	  , dh_finalizacao		DATETIME
	  , us_finalizacao		VARCHAR(38)
	  , dh_cancelamento		DATETIME
	  , us_cancelamento		VARCHAR(38)
	  , id_empresa			VARCHAR(38) NOT NULL
	);
	/*
	ALTER TABLE dbo.tbl_atendimento 
	  add dh_atualizacao DATETIME;

	ALTER TABLE dbo.tbl_atendimento 
	  add us_atualizacao VARCHAR(38);
	*/
	ALTER TABLE dbo.tbl_atendimento ADD FOREIGN KEY (id_empresa)
		REFERENCES dbo.sys_empresa (id_empresa);

	ALTER TABLE dbo.tbl_atendimento ADD FOREIGN KEY (cd_paciente)
		REFERENCES dbo.tbl_paciente (cd_paciente);

	ALTER TABLE dbo.tbl_atendimento ADD FOREIGN KEY (cd_convenio)
		REFERENCES dbo.tbl_convenio (cd_convenio);

	ALTER TABLE dbo.tbl_atendimento ADD FOREIGN KEY (cd_especialidade)
		REFERENCES dbo.tbl_especialidade (cd_especialidade);

	ALTER TABLE dbo.tbl_atendimento ADD FOREIGN KEY (cd_profissional)
		REFERENCES dbo.tbl_profissional (cd_profissional);

	ALTER TABLE dbo.tbl_agenda ADD FOREIGN KEY (id_atendimento)
		REFERENCES dbo.tbl_atendimento (id_atendimento);

	DROP INDEX IF EXISTS idx_dt_atendimento_atend	ON dbo.tbl_atendimento;
	CREATE INDEX idx_dt_atendimento_atend			ON dbo.tbl_atendimento (dt_atendimento, hr_atendimento);

	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'id_atendimento',	N'ID';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'cd_atendimento',	N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'dt_atendimento',	N'Data';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'hr_atendimento',	N'Hora';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'cd_paciente',		N'Paciente';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'cd_convenio',		N'Convênio';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'cd_especialidade',	N'Especialidade';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'cd_profissional',	N'Profissional';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'ds_historia',		N'História clínica';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'ds_prescricao',	N'Prescrição/Receituário';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'st_atendimento',	N'Situação:
	0 - Aberto
	1 - Finalizado
	2 - Faturado
	3 - Cancelado';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'dh_atualizacao',	N'Data/hora da última atualização do atendimento';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'us_atualizacao',	N'Usuário da última atualização finalização do atendimento';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'dh_finalizacao',	N'Data/hora de finalização do atendimento';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'us_finalizacao',	N'Usuário de finalização do atendimento';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'dh_cancelamento',	N'Data/hora de cancelamento do atendimento';
	EXEC dbo.spDocumentarCampo N'tbl_atendimento', N'us_cancelamento',	N'Usuário que cancelou o atendimento';
END
GO

CREATE OR ALTER VIEW dbo.vw_situacao_atendimento
AS
	Select 0 as cd_tipo, 'Aberto' as ds_tipo union
	Select 1, 'Finalizado'	union
	Select 2, 'Faturado'	union
	Select 3, 'Cancelado'	
GO

IF OBJECT_ID (N'dbo.tbl_exame_paciente') IS NULL  
BEGIN
	CREATE TABLE dbo.tbl_exame_paciente (
	    id_lancamento	VARCHAR(38) NOT NULL PRIMARY KEY 
	  , cd_paciente		BIGINT NOT NULL
	  , id_exame		VARCHAR(38) NOT NULL 
	  , dt_exame		DATE NOT NULL
	  , vl_exame		NUMERIC(18,3)
	  , vl_exame_texto	VARCHAR(25)
	  , id_atendimento	VARCHAR(38)
	  , dh_insercao		DATETIME
	  , us_insercao		VARCHAR(38)
	  , dh_atualizacao	DATETIME
	  , us_atualizacao	VARCHAR(38)
	);

	ALTER TABLE dbo.tbl_exame_paciente ADD UNIQUE (cd_paciente, id_exame, dt_exame);

	ALTER TABLE dbo.tbl_exame_paciente ADD FOREIGN KEY (cd_paciente)
		REFERENCES dbo.tbl_paciente (cd_paciente)     
		ON DELETE CASCADE    
		ON UPDATE CASCADE;

	ALTER TABLE dbo.tbl_exame_paciente ADD FOREIGN KEY (id_atendimento)
		REFERENCES dbo.tbl_atendimento (id_atendimento);

	ALTER TABLE dbo.tbl_exame_paciente ADD FOREIGN KEY (id_exame)
		REFERENCES dbo.tbl_exame (id_exame);

	DROP INDEX IF EXISTS idx_ui_exame_paciente	ON dbo.tbl_exame_paciente;
	DROP INDEX IF EXISTS idx_ua_exame_paciente	ON dbo.tbl_exame_paciente;
	CREATE INDEX idx_ui_exame_paciente			ON dbo.tbl_exame_paciente (us_insercao);
	CREATE INDEX idx_ua_exame_paciente			ON dbo.tbl_exame_paciente (us_atualizacao);

	/*
	ALTER TABLE dbo.tbl_exame_paciente ADD FOREIGN KEY (us_insercao)
		REFERENCES dbo.sys_usuario (id_usuario);

	ALTER TABLE dbo.tbl_exame_paciente ADD FOREIGN KEY (us_atualizacao)
		REFERENCES dbo.sys_usuario (id_usuario);
	*/

	EXEC dbo.spDocumentarCampo N'tbl_exame_paciente', N'id_lancamento',		N'ID';
	EXEC dbo.spDocumentarCampo N'tbl_exame_paciente', N'cd_paciente',		N'Paciente';
	EXEC dbo.spDocumentarCampo N'tbl_exame_paciente', N'id_exame',			N'Exame';
	EXEC dbo.spDocumentarCampo N'tbl_exame_paciente', N'dt_exame',			N'Data';
	EXEC dbo.spDocumentarCampo N'tbl_exame_paciente', N'vl_exame',			N'Resultado numérico';
	EXEC dbo.spDocumentarCampo N'tbl_exame_paciente', N'vl_exame_texto',	N'Resultado textual';
	EXEC dbo.spDocumentarCampo N'tbl_exame_paciente', N'id_atendimento',	N'Atendimento';
	EXEC dbo.spDocumentarCampo N'tbl_exame_paciente', N'dh_insercao',		N'Data/hora da inserção';
	EXEC dbo.spDocumentarCampo N'tbl_exame_paciente', N'us_insercao',		N'Usuário da inserção';
	EXEC dbo.spDocumentarCampo N'tbl_exame_paciente', N'dh_atualizacao',	N'Data/hora da atualização';
	EXEC dbo.spDocumentarCampo N'tbl_exame_paciente', N'us_atualizacao',	N'Usuário da atualização';
END
GO

CREATE OR ALTER PROCEDURE dbo.getExamePaciente
	@Empresa	Varchar(38)
  , @Paciente	Bigint
  , @Exame		Varchar(38)
  , @Data		Varchar(12)
AS
BEGIN
	Select 
		p.id_exame
	  , e.cd_exame
	  , e.nm_exame
	  , p.vl_exame
	  , p.vl_exame_texto
	  , e.un_exame
	from dbo.tbl_exame_paciente p
	  inner join dbo.tbl_exame e on (e.id_exame = p.id_exame and e.id_empresa = @Empresa)
	where (p.cd_paciente = @Paciente)
	  and (p.id_exame    = @Exame)
	  and (p.dt_exame    = convert(date, @Data, 103));
END
GO

IF OBJECT_ID (N'dbo.tbl_evolucao_medida_pac') IS NULL  
BEGIN
	CREATE TABLE dbo.tbl_evolucao_medida_pac (
	    id_lancamento	VARCHAR(38) NOT NULL PRIMARY KEY 
	  , cd_paciente		BIGINT NOT NULL
	  , id_evolucao			VARCHAR(38) NOT NULL 
	  , dt_evolucao			DATE NOT NULL
	  , vl_evolucao			NUMERIC(18,3)
	  , vl_evolucao_texto	VARCHAR(25)
	  , id_atendimento		VARCHAR(38)
	  , dh_insercao			DATETIME
	  , us_insercao			VARCHAR(38)
	  , dh_atualizacao		DATETIME
	  , us_atualizacao		VARCHAR(38)
	);

	ALTER TABLE dbo.tbl_evolucao_medida_pac ADD UNIQUE (cd_paciente, id_evolucao, dt_evolucao);

	ALTER TABLE dbo.tbl_evolucao_medida_pac ADD FOREIGN KEY (cd_paciente)
		REFERENCES dbo.tbl_paciente (cd_paciente)     
		ON DELETE CASCADE    
		ON UPDATE CASCADE;

	ALTER TABLE dbo.tbl_evolucao_medida_pac ADD FOREIGN KEY (id_atendimento)
		REFERENCES dbo.tbl_atendimento (id_atendimento);

	ALTER TABLE dbo.tbl_evolucao_medida_pac ADD FOREIGN KEY (id_evolucao)
		REFERENCES dbo.tbl_evolucao (id_evolucao);

	DROP INDEX IF EXISTS idx_ui_evolucao_medica_pac	ON dbo.tbl_evolucao_medida_pac;
	DROP INDEX IF EXISTS idx_ua_evolucao_medica_pac	ON dbo.tbl_evolucao_medida_pac;
	CREATE INDEX idx_ui_evolucao_medica_pac			ON dbo.tbl_evolucao_medida_pac (us_insercao);
	CREATE INDEX idx_ua_evolucao_medica_pac			ON dbo.tbl_evolucao_medida_pac (us_atualizacao);

	/*
	ALTER TABLE dbo.tbl_evolucao_medica_pac ADD FOREIGN KEY (us_insercao)
		REFERENCES dbo.sys_usuario (id_usuario);

	ALTER TABLE dbo.tbl_evolucao_medica_pac ADD FOREIGN KEY (us_atualizacao)
		REFERENCES dbo.sys_usuario (id_usuario);
	*/

	EXEC dbo.spDocumentarCampo N'tbl_evolucao_medida_pac', N'id_lancamento',	N'ID';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao_medida_pac', N'cd_paciente',		N'Paciente';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao_medida_pac', N'id_evolucao',		N'Evolução';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao_medida_pac', N'dt_evolucao',		N'Data';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao_medida_pac', N'vl_evolucao',		N'Valor numérico';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao_medida_pac', N'vl_evolucao_texto',N'Valor textual';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao_medida_pac', N'id_atendimento',	N'Atendimento';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao_medida_pac', N'dh_insercao',		N'Data/hora da inserção';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao_medida_pac', N'us_insercao',		N'Usuário da inserção';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao_medida_pac', N'dh_atualizacao',	N'Data/hora da atualização';
	EXEC dbo.spDocumentarCampo N'tbl_evolucao_medida_pac', N'us_atualizacao',	N'Usuário da atualização';
END
GO

CREATE OR ALTER PROCEDURE dbo.getEvolucaoPaciente
	@Empresa	Varchar(38)
  , @Paciente	Bigint
  , @Evolucao	Varchar(38)
  , @Data		Varchar(12)
AS
BEGIN
	Select 
		p.id_evolucao
	  , e.cd_evolucao
	  , e.ds_evolucao
	  , p.vl_evolucao
	  , p.vl_evolucao_texto
	  , e.un_evolucao
	from dbo.tbl_evolucao_medida_pac p
	  inner join dbo.tbl_evolucao e on (e.id_evolucao = p.id_evolucao and e.id_empresa = @Empresa)
	where (p.cd_paciente = @Paciente)
	  and (p.id_evolucao = @Evolucao)
	  and (p.dt_evolucao = convert(date, @Data, 103));
END
GO

CREATE OR ALTER PROCEDURE dbo.setAgendamentoAvulso
	@Empresa		Varchar(38)
  , @Data			Varchar(10)
  , @Hora			Varchar(8)
  , @Usuario		Varchar(38)
  , @Profissional	Int
  , @Convenio		Int
  , @Tabela			Int
  , @Paciente		Bigint
  , @Situacao		Smallint
  , @Observacoes	Varchar(250)
AS
BEGIN
  DECLARE @atendimento   Int;
  DECLARE @especialidade Int;
  DECLARE @valor_servico NUMERIC(18,2);  
  DECLARE @id_agenda	 Varchar(38);
  DECLARE @insercao		 DateTime;

  Select
      @especialidade = v.cd_especialidade
	, @atendimento   = v.tp_atendimento
	, @valor_servico = coalesce(v.vl_servico, 0.0) 
  from dbo.tbl_tabela_cobranca v
  where (v.id_empresa  = @Empresa) 
    and (v.cd_tabela   = @Tabela)
    and (v.sn_ativo    = 1);

  Select 
      @id_agenda = dbo.ufnGetGuidID()
	, @insercao  = getdate();

  Insert Into dbo.tbl_agenda (
	  id_agenda
	, cd_configuracao
	, id_empresa
	, dt_agenda
	, hr_agenda
	, st_agenda
	, cd_paciente
	, tp_atendimento
	, cd_especialidade
	, cd_profissional
	, cd_tabela
	, cd_servico
	, vl_servico
	, ds_observacao
	, dh_insercao
	, us_insercao
	, sn_avulso
  ) values (
      @id_agenda
	, null
	, @Empresa
	, convert(date, @Data, 103)
	, convert(time, concat(@Hora, '.0000000'), 108)
	, @Situacao
	, @Paciente
	, @atendimento
	, @especialidade
	, @Profissional
	, @Tabela
	, @Tabela
	, @valor_servico
	, @Observacoes
	, getdate()
	, @Usuario
	, 1
  );

  Select 
	  coalesce(a.cd_paciente, 0) as prontuario
	, coalesce(p.nm_paciente, a.nm_paciente)     as paciente
	, convert(varchar(12), p.dt_nascimento, 103) as nascimento
	, left(convert(varchar(12), p.dt_nascimento, 120), 10)     as dt_nasc
	, left(convert(varchar(12), getdate(), 120), 10)           as dt_hoje
	, coalesce(nullif(trim(a.nr_celular), ''), p.nr_celular)   as celular
	, coalesce(nullif(trim(a.nr_telefone), ''), p.nr_telefone) as telefone
	, coalesce(nullif(trim(a.ds_email), ''), p.ds_email)       as email
	, convert(varchar(12), getdate(), 103)   as hoje_agenda
	, convert(varchar(12), a.dt_agenda, 103) as data_agenda
	, convert(varchar(12), a.hr_agenda, 108) as hora_agenda
	, convert(varchar(12), coalesce(at.dt_atendimento, a.dh_alteracao, a.dh_atendimento), 103) as data_atendimento
	, convert(varchar(12), coalesce(at.hr_atendimento, a.dh_alteracao, a.dh_atendimento), 108) as hora_atendimento
	, t.ds_tipo     as tipo
	, s.ds_situacao as situacao
	, c.nm_resumido as convenio
	, v.nm_tabela   as servico
	, e.ds_especialidade as especialidade
	, coalesce(nullif(trim(m.nm_apresentacao), ''), m.nm_profissional)  as profissional
	, u.nm_usuario as atendente
	, a.*
	, p.*
	, coalesce(at.cd_atendimento, 0)    as cd_atendimento
	, coalesce(at.ds_historia, '...')   as ds_historia
	, coalesce(at.ds_prescricao, '...') as ds_prescricao
  from dbo.tbl_agenda a
	left join dbo.tbl_paciente p on (p.cd_paciente = a.cd_paciente)
	left join dbo.vw_tipo_atendimento t on (t.cd_tipo = a.tp_atendimento)
	left join dbo.vw_situacao_agenda s on (s.cd_situacao = a.st_agenda)
	left join dbo.tbl_convenio c on (c.cd_convenio = a.cd_convenio)
	left join dbo.tbl_tabela_cobranca v on (v.cd_tabela = a.cd_tabela)
	left join dbo.tbl_especialidade e on (e.cd_especialidade = a.cd_especialidade)
	left join dbo.tbl_profissional m on (m.cd_profissional = a.cd_profissional)
	left join dbo.sys_usuario u on (u.id_usuario = a.us_alteracao)
    left join dbo.tbl_atendimento at on (at.id_atendimento = a.id_atendimento)

  where (a.id_agenda  = @id_agenda)
	and (a.id_empresa = @Empresa);

END
GO

CREATE OR ALTER PROCEDURE dbo.getAtendimentoPaciente
	@Atendimento	Varchar(38)
  , @Empresa		Varchar(38)
AS
BEGIN
	Select 
		a.cd_paciente
	  , p.nm_paciente
	  , convert(varchar(12), p.dt_nascimento, 103) as nascimento
	  , left(convert(varchar(12), p.dt_nascimento, 120), 10)     as dt_nasc
	  , left(convert(varchar(12), getdate(), 120), 10)           as dt_hoje
	  , p.nr_celular  as celular
	  , p.nr_telefone as telefone
	  , p.ds_email    as email
	  , convert(varchar(12), getdate(), 103)		as hoje_agenda
	  , convert(varchar(12), a.dt_atendimento, 103) as data_atendimento
	  , convert(varchar(12), a.hr_atendimento, 108) as hora_atendimento
	  , t.ds_tipo     as situacao
	  , c.nm_resumido as convenio
	  , e.ds_especialidade as especialidade
	  , coalesce(nullif(trim(m.nm_apresentacao), ''), m.nm_profissional)  as profissional
	  , m.ds_conselho
	  , m.ft_assinatura
	  , a.*
	  , p.*
	  , a.id_empresa as empresa
	from dbo.tbl_atendimento a
	  left join dbo.tbl_paciente p on (p.cd_paciente = a.cd_paciente)
	  left join dbo.vw_situacao_atendimento t on (t.cd_tipo = a.st_atendimento)
	  left join dbo.tbl_convenio c on (c.cd_convenio = a.cd_convenio)
	  left join dbo.tbl_especialidade e on (e.cd_especialidade = a.cd_especialidade)
	  left join dbo.tbl_profissional m on (m.cd_profissional = a.cd_profissional)

	where (a.id_atendimento = @Atendimento)
	  and (a.id_empresa     = @Empresa);
END
GO

CREATE OR ALTER PROCEDURE dbo.getAgendamentoPaciente
	@Agenda		Varchar(38)
  , @Empresa	Varchar(38)
AS
BEGIN
	Select 
		ag.cd_paciente
	  , p.nm_paciente
	  , convert(varchar(12), p.dt_nascimento, 103) as nascimento
	  , left(convert(varchar(12), p.dt_nascimento, 120), 10)     as dt_nasc
	  , left(convert(varchar(12), getdate(), 120), 10)           as dt_hoje
	  , p.nr_celular  as celular
	  , p.nr_telefone as telefone
	  , p.ds_email    as email
	  , convert(varchar(12), getdate(), 103)		as hoje_agenda
	  , convert(varchar(12), coalesce(a.dt_atendimento, ag.dt_agenda), 103) as data_atendimento
	  , convert(varchar(12), coalesce(a.hr_atendimento, ag.hr_agenda), 108) as hora_atendimento
	  , coalesce(t.ds_tipo, ta.ds_situacao) as situacao
	  , c.nm_resumido as convenio
	  , e.ds_especialidade as especialidade
	  , coalesce(nullif(trim(m.nm_apresentacao), ''), m.nm_profissional)  as profissional
	  , m.ds_conselho
	  , m.ft_assinatura
	  , ag.*
	  , a.*
	  , p.*
	  , coalesce(a.id_empresa, ag.id_empresa) as empresa
	from dbo.tbl_agenda ag
	  left join dbo.tbl_paciente p on (p.cd_paciente = ag.cd_paciente)
	  left join dbo.tbl_atendimento a on (a.id_atendimento = ag.id_atendimento)
	  left join dbo.vw_situacao_agenda ta on (ta.cd_situacao = ag.st_agenda)
	  left join dbo.vw_situacao_atendimento t on (t.cd_tipo = a.st_atendimento)
	  left join dbo.tbl_convenio c on (c.cd_convenio = coalesce(a.cd_convenio, ag.cd_convenio))
	  left join dbo.tbl_especialidade e on (e.cd_especialidade = coalesce(a.cd_especialidade, ag.cd_especialidade))
	  left join dbo.tbl_profissional m on (m.cd_profissional = coalesce(a.cd_profissional, ag.cd_profissional))

	where (ag.id_agenda  = @Agenda)
	  and (ag.id_empresa = @Empresa);
END
GO

CREATE OR ALTER PROCEDURE dbo.getDadosPaciente
    @Paciente	Bigint
  , @Agenda		Varchar(38)
  , @Empresa	Varchar(38)
AS
BEGIN
	Select 
		p.cd_paciente
	  , p.nm_paciente
	  , convert(varchar(12), p.dt_nascimento, 103) as nascimento
	  , left(convert(varchar(12), p.dt_nascimento, 120), 10)     as dt_nasc
	  , left(convert(varchar(12), getdate(), 120), 10)           as dt_hoje
	  , p.nr_celular  as celular
	  , p.nr_telefone as telefone
	  , p.ds_email    as email
	  , convert(varchar(12), getdate(), 103)		as hoje_agenda
	  , convert(varchar(12), coalesce(a.dt_atendimento, ag.dt_agenda, getdate()), 103) as data_atendimento
	  , convert(varchar(12), coalesce(a.hr_atendimento, ag.hr_agenda, getdate()), 108) as hora_atendimento
	  , coalesce(t.ds_tipo, ta.ds_situacao, 'Cadastrado') as situacao
	  , c.nm_resumido as convenio
	  , e.ds_especialidade as especialidade
	  , coalesce(nullif(trim(m.nm_apresentacao), ''), m.nm_profissional)  as profissional
	  , m.ds_conselho
	  , m.ft_assinatura
	  , ag.*
	  , a.*
	  , p.*
	  , coalesce(a.id_empresa, ag.id_empresa) as empresa
	from dbo.tbl_paciente p
	  left join dbo.tbl_agenda ag on (ag.cd_paciente = p.cd_paciente and ag.id_agenda  = @Agenda and ag.id_empresa = @Empresa)
	  left join dbo.tbl_atendimento a on (a.id_atendimento = ag.id_atendimento)
	  left join dbo.vw_situacao_agenda ta on (ta.cd_situacao = ag.st_agenda)
	  left join dbo.vw_situacao_atendimento t on (t.cd_tipo = a.st_atendimento)
	  left join dbo.tbl_convenio c on (c.cd_convenio = coalesce(a.cd_convenio, ag.cd_convenio, p.cd_convenio))
	  left join dbo.tbl_especialidade e on (e.cd_especialidade = coalesce(a.cd_especialidade, ag.cd_especialidade))
	  left join dbo.tbl_profissional m on (m.cd_profissional = coalesce(a.cd_profissional, ag.cd_profissional))

	where (p.cd_paciente = @Paciente);
END
GO

CREATE OR ALTER PROCEDURE dbo.setUsuarioEmpresa
	@Empresa	Varchar(38)
  , @Usuario	Varchar(38)
  , @Ativo		Smallint
AS
BEGIN
  DECLARE @ativacao	DateTime;

  Select 
	@ativacao = Case when (coalesce(@Ativo, 0) = 1) then getdate() else null end;

  if (not exists(
    Select
	  e.id_usuario
	from dbo.sys_usuario_empresa e
	where e.id_usuario = @Usuario
	  and e.id_empresa = @Empresa
  )) 
  Begin
    Insert Into dbo.sys_usuario_empresa (
		id_usuario
	  , id_empresa
	  , sn_ativo
	  , dh_ativacao
	) values (
		@Usuario
	  , @Empresa
	  , @Ativo
	  , @ativacao
	);
  End
  Else
  Begin
    Update dbo.sys_usuario_empresa Set 
	    sn_ativo	 = @Ativo
	  , dh_ativacao  = @ativacao
	where id_usuario = @Usuario
	  and id_empresa = @Empresa;
  End
END
GO

CREATE OR ALTER PROCEDURE dbo.spGerarListaExamesPaciente
	@Empresa	nvarchar(38)
  , @Paciente	bigint
  , @Data		nvarchar(10)
  , @Usuario	nvarchar(38)
AS
BEGIN
  if (exists(
    Select
	  e.id_empresa
	from dbo.sys_empresa e
	where e.id_empresa = @Empresa
  ) and exists(
    Select
	  p.cd_paciente
	from dbo.tbl_paciente p
	where p.cd_paciente = @Paciente
  )) 
  Begin
  
    -- Excluir históricos vazios de evoluções de medidas do paciente
    Delete from dbo.tbl_exame_paciente
	where cd_paciente = @Paciente
	  and dt_exame != convert(date, @Data, 103)
	  and vl_exame is null
	  and vl_exame_texto is null
	  and id_atendimento is null;

    -- Inserir históricos vazios de evoluções de medidas para o paciente na data informada
	Insert Into dbo.tbl_exame_paciente (
	    id_lancamento	
	  , cd_paciente		
	  , id_exame		
	  , dt_exame		
	  , vl_exame		
	  , vl_exame_texto	
	  , id_atendimento	
	  , dh_insercao		
	  , us_insercao		
	) Select 
	    dbo.ufnGetGuidID()	
	  , @Paciente		
	  , e.id_exame
	  , convert(date, @Data, 103)		
	  , NULL		
	  , NULL
	  , NULL
	  , getdate()
	  , @Usuario		
	from dbo.tbl_exame e
	  left join dbo.tbl_exame_paciente a on (a.cd_paciente = @Paciente and a.id_exame = e.id_exame)
	where (e.sn_ativo = 1)
	  and (e.id_empresa = @Empresa)
	  and (a.id_lancamento is null)
	order by e.nm_exame;
  End
END
GO

CREATE OR ALTER PROCEDURE dbo.spGerarListaEvolucaoMedidaPaciente
	@Empresa	nvarchar(38)
  , @Paciente	bigint
  , @Data		nvarchar(10)
  , @Usuario	nvarchar(38)
AS
BEGIN
  if (exists(
    Select
	  e.id_empresa
	from dbo.sys_empresa e
	where e.id_empresa = @Empresa
  ) and exists(
    Select
	  p.cd_paciente
	from dbo.tbl_paciente p
	where p.cd_paciente = @Paciente
  )) 
  Begin
    -- Excluir históricos vazios de evoluções de medidas do paciente
    Delete from dbo.tbl_evolucao_medida_pac
	where cd_paciente  = @Paciente
	  and dt_evolucao != convert(date, @Data, 103)
	  and vl_evolucao is null
	  and vl_evolucao_texto is null
	  and id_atendimento is null;

    -- Inserir históricos vazios de evoluções de medidas para o paciente na data informada
	Insert Into dbo.tbl_evolucao_medida_pac (
	    id_lancamento	
	  , cd_paciente		
	  , id_evolucao			
	  , dt_evolucao			
	  , vl_evolucao			
	  , vl_evolucao_texto	
	  , id_atendimento		
	  , dh_insercao			
	  , us_insercao			
	) Select 
	    dbo.ufnGetGuidID()	
	  , @Paciente		
	  , e.id_evolucao
	  , convert(date, @Data, 103)		
	  , NULL		
	  , NULL
	  , NULL
	  , getdate()
	  , @Usuario		
	from dbo.tbl_evolucao e
	  left join dbo.tbl_evolucao_medida_pac a on (a.cd_paciente = @Paciente and a.id_evolucao = e.id_evolucao)
	where (e.sn_ativo = 1)
	  and (e.id_empresa = @Empresa)
	  and (a.id_lancamento is null)
	order by e.ds_evolucao;
  End
END
GO

IF OBJECT_ID (N'dbo.sys_grupo_arquivo') IS NULL  
BEGIN
	CREATE TABLE dbo.sys_grupo_arquivo (
	    cd_grupo	INT IDENTITY(1,1) NOT NULL PRIMARY KEY
	  , ds_grupo	VARCHAR(50)
	  , sn_ativo	SMALLINT DEFAULT 1 NOT NULL CHECK ((sn_ativo = 0) or (sn_ativo = 1))
	);

	EXEC dbo.spDocumentarCampo N'sys_grupo_arquivo', N'cd_grupo',	N'Código';
	EXEC dbo.spDocumentarCampo N'sys_grupo_arquivo', N'ds_grupo',	N'Descrição';
	EXEC dbo.spDocumentarCampo N'sys_grupo_arquivo', N'sn_ativo',	N'Ativo:
	0 - Não
	1 - Sim';
END
GO

IF OBJECT_ID (N'dbo.tbl_arquivo_paciente') IS NULL  
BEGIN
	CREATE TABLE dbo.tbl_arquivo_paciente (
		id_arquivo		VARCHAR(38) PRIMARY KEY 
	  , cd_arquivo		BIGINT IDENTITY(1,1) NOT NULL UNIQUE
	  , ds_arquivo		VARCHAR(250) 
	  , nm_arquivo		VARCHAR(150)
	  , tp_arquivo		VARCHAR(50)
	  , ex_arquivo		VARCHAR(10)
	  , dt_arquivo		DATE
	  , ur_arquivo		VARCHAR(250)
	  , cd_grupo		INT NOT NULL
	  , cd_paciente		BIGINT NOT NULL
	  , id_empresa		VARCHAR(38) NOT NULL
	  , dh_insercao		DATETIME
	  , us_insercao		VARCHAR(38)
	  , dh_alteracao	DATETIME
	  , us_alteracao	VARCHAR(38)
	  , hs_estacao		VARCHAR(150)
	  , sn_ativo		SMALLINT DEFAULT 0 NOT NULL CHECK ((sn_ativo = 0) or (sn_ativo = 1))
	);

	ALTER TABLE dbo.tbl_arquivo_paciente ADD FOREIGN KEY (cd_grupo)
		REFERENCES dbo.sys_grupo_arquivo (cd_grupo);

	ALTER TABLE dbo.tbl_arquivo_paciente ADD FOREIGN KEY (id_empresa)
		REFERENCES dbo.sys_empresa (id_empresa);

	ALTER TABLE dbo.tbl_arquivo_paciente ADD FOREIGN KEY (cd_paciente)
		REFERENCES dbo.tbl_paciente (cd_paciente);

	DROP INDEX IF EXISTS idx_arquivo_paciente_url	ON dbo.tbl_arquivo_paciente;
	CREATE INDEX idx_arquivo_paciente_url			ON dbo.tbl_arquivo_paciente (ur_arquivo);

	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'id_arquivo',		N'ID';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'cd_arquivo',		N'Código';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'ds_arquivo',		N'Descrição';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'nm_arquivo',		N'Nome';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'tp_arquivo',		N'Tipo';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'ex_arquivo',		N'Extenção';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'dt_arquivo',		N'Data';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'ur_arquivo',		N'URL';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'cd_paciente',		N'Paciente';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'id_empresa',		N'Empresa';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'dh_insercao',		N'Data/hora da inserção';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'us_insercao',		N'Usuário da inserção';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'dh_alteracao',	N'Data/hora da última atualização';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'us_alteracao',	N'Usuário da última atualização';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'hs_estacao',		N'MD5 da estação de origem dos dados';
	EXEC dbo.spDocumentarCampo N'tbl_arquivo_paciente', N'sn_ativo',		N'Ativo:
	0 - Não
	1 - Sim';
END
GO

CREATE OR ALTER PROCEDURE dbo.getAgendaAtendimentos
    @Filtro		Int
  , @Data		Varchar(10)
  , @Empresa	Varchar(38)
AS
BEGIN
  if ((@Data != '') and (@Empresa != ''))
  Begin
	Select
	  *
	from (
		Select  
			a.* 
		  , convert(varchar(12), a.dt_agenda, 103) as data_agenda 
		  , convert(varchar(8),  a.hr_agenda, 108) as hora_agenda 
		  , coalesce(p.nm_paciente, a.nm_paciente, '...') as paciente       
		  , coalesce(nullif(a.nr_celular, ''), nullif(a.nr_telefone, ''), nullif(p.nr_celular, ''), nullif(p.nr_telefone, ''), '...') as contato
		  , t.ds_tipo as ds_atendimento 
		  , s.ds_situacao               
		  , coalesce(e.ds_especialidade, '...') as ds_especialidade 
		  , coalesce(m.nm_profissional,  '...') as nm_profissional  
		  , left(convert(varchar(12), p.dt_nascimento, 120), 10) as dt_nasc  
		  , left(convert(varchar(12), getdate(), 120), 10)       as dt_hoje  
		  , coalesce(at.cd_atendimento, 0)   as codigo_atendimento           
		  , convert(varchar(12), coalesce(at.dt_atendimento, a.dt_agenda), 103) as data_atendimento  
		  , case when a.st_agenda = 4 then 1 else 0 end as idx_ordem -- Deixar os agendamentos cancelados no final
		from dbo.tbl_agenda a 
		  inner join dbo.vw_situacao_agenda s on (s.cd_situacao = a.st_agenda)       
		  inner join dbo.vw_tipo_atendimento t on (t.cd_tipo = a.tp_atendimento)     
		  left join dbo.tbl_paciente p on (p.cd_paciente = a.cd_paciente)               
		  left join dbo.tbl_especialidade e on (e.cd_especialidade = a.cd_especialidade)
		  left join dbo.tbl_profissional m on (m.cd_profissional = a.cd_profissional)   
		  left join dbo.tbl_atendimento at on (at.id_atendimento = a.id_atendimento)
		where (a.id_empresa = @Empresa) 
		  and (a.dt_agenda  = convert(date, @Data, 103)) 
		  and ((@Filtro = 0 and (a.st_agenda between 1 and 4)) -- Todos
		    or (@Filtro = 1 and (a.st_agenda between 2 and 3)) -- Apenas Confirmados
		  )
	) x

	order by     
		x.idx_ordem
	  , x.hr_agenda 
  End
END
GO
