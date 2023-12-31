SET IDENTITY_INSERT dbo.sys_bairro ON
GO

BEGIN TRANSACTION
GO

/* Data for the 'dbo.sys_bairro' table  (Records 1 - 114) */

INSERT INTO [dbo].[sys_bairro]
  ([CD_BAIRRO], [NM_BAIRRO], [CD_CIDADE])
VALUES 
  (1, N'�GUAS BRANCAS', 1500800),
  (3, N'�GUAS LINDAS', 1500800),
  (4, N'ATALAIA', 1500800),
  (5, N'AUR�', 1500800),
  (6, N'CENTRO', 1500800),
  (7, N'CIDADE NOVA', 1500800),
  (8, N'COQUEIRO', 1500800),
  (9, N'CURU�AMB�', 1500800),
  (10, N'DISTRITO INDUSTRIAL', 1500800),
  (11, N'GUANABARA', 1500800),
  (12, N'ICU�-GUAJAR�', 1500800),
  (13, N'ICU�-LARANJEIRA', 1500800),
  (14, N'ILHA JO�O PILATOS', 1500800),
  (15, N'JIB�IA BRANCA', 1500800),
  (16, N'LEVIL�NDIA', 1500800),
  (17, N'MAGUARI', 1500800),
  (18, N'MARITUBA', 1500800),
  (19, N'PATO MACHO', 1500800),
  (20, N'QUARENTA HORAS (COQUEIRO)', 1500800),
  (21, N'MARCO', 1501402),
  (22, N'��GUA BOA (OUTEIRO)', 1501402),
  (23, N'�GUAS LINDAS', 1501402),
  (24, N'��GUAS NEGRAS (ICOARACI)', 1501402),
  (25, N'AEROPORTO (MOSQUEIRO)', 1501402),
  (26, N'AGULHA (ICOARACI)', 1501402),
  (27, N'ARIRAMBA (MOSQUEIRO)', 1501402),
  (28, N'AUR�', 1501402),
  (29, N'BA�A DO SOL (MOSQUEIRO)', 1501402),
  (30, N'BARREIRO', 1501402),
  (31, N'BATISTA CAMPOS', 1501402),
  (32, N'BENGUI', 1501402),
  (33, N'BONFIM (MOSQUEIRO)', 1501402),
  (34, N'BRAS�LIA (OUTEIRO)', 1501402),
  (35, N'CABANAGEM', 1501402),
  (36, N'CAMPINA', 1501402),
  (37, N'CAMPINA DE ICOARACI (ICOARACI)', 1501402),
  (38, N'CANUDOS', 1501402),
  (39, N'CARANANDUBA (MOSQUEIRO)', 1501402),
  (40, N'CARUARA (MOSQUEIRO)', 1501402),
  (41, N'CASTANHEIRA', 1501402),
  (42, N'CHAP�U VIRADO (MOSQUEIRO)', 1501402),
  (43, N'CIDADE VELHA', 1501402),
  (44, N'CONDOR', 1501402),
  (45, N'COQUEIRO', 1501402),
  (46, N'CREMA��O', 1501402),
  (47, N'CRUZEIRO (ICOARACI)', 1501402),
  (48, N'CURI�-UTINGA', 1501402),
  (49, N'CUTIJUBA', 1501402),
  (50, N'FAROL (MOSQUEIRO)', 1501402),
  (51, N'F�TIMA', 1501402),
  (52, N'GUAM�', 1501402),
  (53, N'ITAITEUA (OUTEIRO)', 1501402),
  (54, N'JURUNAS', 1501402),
  (55, N'MANGUEIR�O', 1501402),
  (56, N'MANGUEIRAS (MOSQUEIRO)', 1501402),
  (57, N'MARACACUERA (ICOARACI)', 1501402),
  (58, N'MARACAJ� (MOSQUEIRO)', 1501402),
  (59, N'MARACANGALHA', 1501402),
  (60, N'MARAHU (MOSQUEIRO)', 1501402),
  (61, N'MARAMBAIA', 1501402),
  (62, N'MIRAMAR', 1501402),
  (63, N'MURUBIRA (MOSQUEIRO)', 1501402),
  (64, N'NATAL DO MURUBIRA (MOSQUEIRO)', 1501402),
  (65, N'NAZAR�', 1501402),
  (66, N'PARACURI (ICOARACI)', 1501402),
  (67, N'PARA�SO (MOSQUEIRO)', 1501402),
  (68, N'PARQUE GUAJAR� (ICOARACI)', 1501402),
  (69, N'PARQUE VERDE', 1501402),
  (70, N'PEDREIRA', 1501402),
  (71, N'PONTA GROSSA (ICOARACI)', 1501402),
  (72, N'PORTO ARTHUR (MOSQUEIRO)', 1501402),
  (73, N'PRAIA GRANDE (MOSQUEIRO)', 1501402),
  (74, N'PRATINHA (ICOARACI)', 1501402),
  (75, N'REDUTO', 1501402),
  (76, N'SACRAMENTA', 1501402),
  (77, N'S�O BR�S', 1501402),
  (78, N'S�O CLEMENTE', 1501402),
  (79, N'S�O FRANCISCO (MOSQUEIRO)', 1501402),
  (80, N'S�O JO�O DO OUTEIRO (OUTEIRO)', 1501402),
  (81, N'SOUZA', 1501402),
  (82, N'SUCURIJUQUARA (MOSQUEIRO)', 1501402),
  (83, N'TAPAN� (ICOARACI)', 1501402),
  (84, N'TEL�GRAFO SEM FIO', 1501402),
  (85, N'TENON�', 1501402),
  (86, N'TERRA FIRME', 1501402),
  (87, N'UMARIZAL', 1501402),
  (88, N'UNA', 1501402),
  (89, N'VAL-DE-C�ES', 1501402),
  (90, N'VILA (MOSQUEIRO)', 1501402),
  (91, N'AGROVILA IRACEMA', 1502400),
  (92, N'APE�', 1502400),
  (93, N'BET�NIA', 1502400),
  (94, N'CAI�ARA', 1502400),
  (95, N'CARIRI', 1502400),
  (96, N'CENTRO', 1502400),
  (97, N'CRISTO REDENTOR', 1502400),
  (98, N'ESTRELA', 1502400),
  (99, N'FONTE BOA', 1502400),
  (100, N'HELIOL�NDIA', 1502400),
  (101, N'IANETAMA', 1502400),
  (102, N'IMPERADOR', 1502400),
  (103, N'JADERL�NDIA', 1502400),
  (104, N'JARDIM DAS AC�CIAS', 1502400),
  (105, N'NOVA OLINDA', 1502400),
  (106, N'NOVO ESTRELA', 1502400),
  (107, N'PIRAPORA', 1502400),
  (108, N'SALGADINHO', 1502400),
  (109, N'SANTA CATARINA', 1502400),
  (110, N'SANTA L�DIA', 1502400),
  (111, N'S�O JOS�', 1502400),
  (112, N'SAUDADE I', 1502400),
  (113, N'SAUDADE II', 1502400),
  (114, N'TITANL�NDIA', 1502400),
  (117, N'ZONAL RURAL', 1502400)
GO

COMMIT
GO

SET IDENTITY_INSERT dbo.sys_bairro OFF
GO
