Select *
from dbo.sys_usuario_empresa
go
	Select Top 1
	  u.id_usuario
	from dbo.sys_usuario u
	  left join dbo.sys_usuario_empresa x on (x.id_usuario = u.id_usuario)
	where (x.id_usuario is null);

go

	Select Top 1
	  e.id_empresa
	from dbo.sys_empresa e
	  left join dbo.sys_usuario_empresa x on (x.id_empresa = e.id_empresa)
go
/*
	Insert Into dbo.sys_usuario_empresa 
		values (
			'{504DFBC1-8D58-4907-BF0F-10C6BA7E248D}'
		  , '{338A525E-4F71-47DC-8DD8-835103BEDEBB}'
		  , 1
		  , GETDATE()
		);
*/