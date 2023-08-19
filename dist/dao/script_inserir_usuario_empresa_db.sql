BEGIN
	Declare @Usuario varchar(38);
	Declare @Empresa varchar(38);

	Set @Usuario = null;
	Set @Empresa = null;

	Select Top 1
	  @Usuario = u.id_usuario
	from dbo.sys_usuario u
	  left join dbo.sys_usuario_empresa x on (x.id_usuario = u.id_usuario)
	where (x.id_usuario is null);

	Select Top 1
	  @Empresa = e.id_empresa
	from dbo.sys_empresa e
	  left join dbo.sys_usuario_empresa x on (x.id_empresa = e.id_empresa)
	where (x.id_empresa is null);

	if ( (coalesce(@Usuario, '') <> '') and (coalesce(@Empresa, '') <> '') )
	Begin
		Insert Into dbo.sys_usuario_empresa 
		values (
			@Usuario
		  , @Empresa
		  , 1
		  , GETDATE()
		);

		Update dbo.sys_usuario Set
		   cd_perfil = 1
		where (id_usuario = @Usuario)
		  and (cd_perfil is null);
	End

	if (coalesce(@Empresa, '') <> '')
	Begin
		Update dbo.tbl_profissional Set
			id_empresa = @Empresa
		where id_empresa is null;
	End
END
