Use SystemGCM
GO
BEGIN TRANSACTION
GO

Insert Into dbo.sys_usuario (
    id_usuario
  , nm_usuario
  , ds_email
  , ds_senha
  , cd_perfil
) values 
    (N'{65437733-16BD-4B99-9525-11693EA7DAD7}', N'Isaque Marinho Ribeiro', N'isaque.ribeiro@outlook.com', N'2a2c76a6f4b2608b344107acf3e04636e5b6bef9', 1)
  , (N'{9491E4EC-93E2-4FC1-B980-1524A7CE6B3E}', N'Rubens Tofolo Junior',   N'rubens@gcm.com.br',          N'129c35833a169aaafdae7f9fcff06c34328a70d1', 5)
  , (N'{D6E39A8B-90E8-4E8B-912B-2CF0BC513AFC}', N'Recepção Consoltório',   N'tofolo@hotmail.com',         N'd430606d367a3acb70762b2384ec255b73448106', 4)
GO

COMMIT
GO
