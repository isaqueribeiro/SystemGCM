Use SystemGCM
GO
Insert Into dbo.sys_empresa (
    id_empresa	
  , nm_empresa	
  , nm_fantasia	
  , nr_cnpj_cpf	
  , cd_estado
  , cd_cidade
) values (
	dbo.ufnGetGuidID()
  , 'CONSULTORIO DR. RUBENS TOFOLO JUNIOR EIRELI'
  , 'Consultório Dr. Rubens Tofolo Junior'
  , '29.540.265/0001-64'
  , 15
  , 1501402
);
GO

Use SystemGCM
GO
Insert Into dbo.tbl_convenio (
	  tp_convenio
	, nm_convenio
	, nm_resumido
	, nr_cnpj_cpf
	, nr_registro_ans
	, sn_ativo
) values (
	  1
	, 'Consultório Dr. Rubens Tofolo Junior'
	, 'Particular'
	, '29.540.265/0001-64'
	, '000000'
	, 1
);
GO

Update dbo.sys_empresa Set 
    nm_fantasia = 'Consultório Dr. Rubens Tofolo Junior'
  , ds_endereco = 'Av. Serzedelo Correa, 370, Sala 205/206 - Nazaré - Belém/PA - Cep 66.035-400'
  , ds_contatos = 'Fone: (91) 3241-2394'
  , ds_email    = 'consultoriotofolojunior@hotmail.com';

Use SystemGCM
GO
Insert Into dbo.tbl_profissional (
	  nm_profissional
	, nm_apresentacao
	, ds_conselho
	, sn_ativo
) values (
	  'Rubens Tofolo Junior'
	, 'Dr. Rubens Tofolo Junior'
	, '6402 CRM/PA'
	, 1
);
GO

BEGIN
	Declare @Empresa varchar(38);

	Set @Empresa = null;

	Select Top 1
	  @Empresa = e.id_empresa
	from dbo.sys_empresa e;

	if (coalesce(@Empresa, '') <> '')
	Begin
		Update dbo.tbl_profissional Set
			id_empresa = @Empresa
		where id_empresa is null;
	End
END
