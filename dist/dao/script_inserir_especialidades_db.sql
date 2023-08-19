Use SystemGCM
GO

BEGIN TRANSACTION
GO

/* Data for the 'dbo.sys_tipo_logradouro' table  (Records 1 - 143) */

INSERT INTO dbo.tbl_grupo_espec
  (ds_grupo, nr_tuss)
VALUES 
  (N'CONSULTAS ', N'00.01'),
  (N'TRATAMENTO CLÍNICO  ', N'00.02')
GO
COMMIT
GO

BEGIN TRANSACTION
GO
INSERT INTO DBO.TBL_ESPECIALIDADE (nm_especialidade, ds_especialidade, cd_grupo, nr_tuss) VALUES
  ('Consulta com Clínico Geral', 'Clínico Geral', 1, '00.01.001-4'), 
  ('Consulta em Pronto Socorro', 'Consulta em Pronto Socorro', 1, '00.01.002-3'), 
  ('Consulta com Hepatologista', 'Hepatologista', 1, '00.01.006-5'), 
  ('Consulta com Reumatologista', 'Reumatologista', 1, '00.01.013-8'), 
  ('Consulta com Nefrologista', 'Nefrologista', 1, '00.01.015-4'), 
  ('Consulta com Anestesiologista', 'Anestesiologista', 1, '00.01.016-2'), 
  ('Consulta com Nutrólogo', 'Nutrólogo', 1, '00.01.017-0'), 
  ('Consulta com Médico do Trabalho', 'Médico do Trabalho', 1, '00.01.018-9'), 
  ('Consulta com Alergologista', 'Alergologista', 1, '00.01.019-7'), 
  ('Consulta com Cardiologista', 'Cardiologista', 1, '00.01.020-0'), 
  ('Consulta com Gastroenterologista Clínico', 'Gastroenterologista Clínico', 1, '00.01.023-5'), 
  ('Consulta com Fisiatra', 'Fisiatra', 1, '00.01.025-1'), 
  ('Consulta com Geneticista', 'Geneticista', 1, '00.01.026-0'), 
  ('Consulta com Hematologista', 'Hematologista', 1, '00.01.027-8'), 
  ('Consulta com Pneumologista', 'Pneumologista', 1, '00.01.029-4'), 
  ('Consulta com Oncologista', 'Oncologista', 1, '00.01.030-8'), 
  ('Consulta com Angiologista - Cirurgião Vascular', 'Angiologista - Cirurgião Vascular', 1, '00.01.039-1'), 
  ('Consulta com Cirurgião Cardíaco - Hemodinamicista', 'Cirurgião Cardíaco - Hemodinamicista', 1, '00.01.040-5'), 
  ('Consulta com Cirurgião de Cabeça e Pescoço', 'Cirurgião de Cabeça e Pescoço', 1, '00.01.041-3'), 
  ('Consulta com Dermatologista', 'Dermatologista', 1, '00.01.042-1'), 
  ('Consulta com Cirurgião Geral', 'Cirurgião Geral', 1, '00.01.043-0'), 
  ('Consulta com Cirurgião Endocrinológico', 'Cirurgião Endocrinológico', 1, '00.01.044-8'), 
  ('Consulta com Ginecologista e Obstétra', 'Ginecologista e Obstétra', 1, '00.01.045-6'), 
  ('Consulta com Especialista em Microcirurgia Reconstrutiva', 'Especialista em Microcirurgia Reconstrutiva', 1, '00.01.046-4'), 
  ('Consulta com Mastologista', 'Mastologista', 1, '00.01.047-2'), 
  ('Consulta com Cirurgião da Mão', 'Cirurgião da Mão', 1, '00.01.048-0'), 
  ('Consulta com Neurocirurgião', 'Neurocirurgião', 1, '00.01.049-9'), 
  ('Consulta com Oftalmologista', 'Oftalmologista', 1, '00.01.050-2'), 
  ('Consulta com Otorrinolaringologista', 'Otorrinolaringologista', 1, '00.01.051-0'), 
  ('Consulta com Ortopedista', 'Ortopedista', 1, '00.01.052-9'), 
  ('Consulta com Cirurgião Pediátrico', 'Cirurgião Pediátrico', 1, '00.01.053-7'), 
  ('Consulta com Cirurgião Plástico', 'Cirurgião Plástico', 1, '00.01.054-5'), 
  ('Consulta com Cirurgião Torácico', 'Cirurgião Torácico', 1, '00.01.055-3'), 
  ('Consulta com Urologista', 'Urologista', 1, '00.01.056-1'), 
  ('Consulta com Pediatra', 'Pediatra', 1, '00.01.070-7'), 
  ('Consulta com Homeopata', 'Homeopata', 1, '00.01.071-5'), 
  ('Consulta com Psiquiatra', 'Psiquiatra', 1, '00.01.072-3'), 
  ('Consulta com Endocrinologista', 'Endocrinologista', 1, '00.01.073-1'), 
  ('Consulta com Geriatra', 'Geriatra', 1, '00.01.074-0'), 
  ('Consulta com Infectologista', 'Infectologista', 1, '00.01.075-8'), 
  ('Consulta com Neurologista', 'Neurologista', 1, '00.01.076-6'), 
  ('Consulta com Acupunturista', 'Acupunturista', 1, '00.01.077-4'), 
  ('Consulta com Cirurgião do Aparelho Digestivo', 'Cirurgião do Aparelho Digestivo', 1, '00.01.078-2'), 
  ('Consulta com Proctologista', 'Proctologista', 1, '00.01.079-0'), 


  ('Visita hospitalar com Clínico Geral', 'Visita hosp. Clínico Geral', 1, '00.02.001-0'), 
  ('Visita hospitalar com Hepatologista', 'Visita hosp. Hepatologista', 1, '00.02.006-0'), 
  ('Visita hospitalar com Reumatologista', 'Visita hosp. Reumatologista', 1, '00.02.013-3'), 
  ('Visita hospitalar com Nefrologista', 'Visita hosp. Nefrologista', 1, '00.02.015-0'), 
  ('Visita hospitalar com Nutrólogo', 'Visita hosp. Nutrólogo', 1, '00.02.017-6'), 
  ('Visita hospitalar com Alergologista', 'Visita hosp. Alergologista', 1, '00.02.019-2'), 
  ('Visita hospitalar com Cardiologista', 'Visita hosp. Cardiologista', 1, '00.02.020-6'), 
  ('Visita hospitalar com Gastroenterologista Clínico', 'Visita hosp. Gastroenterologista Clínico', 1, '00.02.023-0'), 
  ('Visita hospitalar com Fisiatra', 'Visita hosp. Fisiatra', 1, '00.02.025-7'), 
  ('Visita hospitalar com Geneticista', 'Visita hosp. Geneticista', 1, '00.02.026-5'), 
  ('Visita hospitalar com Hematologista', 'Visita hosp. Hematologista', 1, '00.02.027-3'), 
  ('Visita hospitalar com Pneumologista', 'Visita hosp. Pneumologista', 1, '00.02.029-0'), 
  ('Visita hospitalar com Oncologista', 'Visita hosp. Oncologista', 1, '00.02.030-3'), 
  ('Visita hospitalar com Angiologista - Cirurgião Vascular', 'Visita hosp. Angiologista - Cirurgião Vascular', 1, '00.02.039-7'), 
  ('Visita hospitalar com Cirurgião Cardíaco - Hemodinamicista', 'Visita hosp. Cirurgião Cardíaco - Hemodinamicista', 1, '00.02.040-0'), 
  ('Visita hospitalar com Cirurgião de Cabeça e Pescoço', 'Visita hosp. Cirurgião de Cabeça e Pescoço', 1, '00.02.041-9'), 
  ('Visita hospitalar com Dermatologista', 'Visita hosp. Dermatologista', 1, '00.02.042-7'), 
  ('Visita hospitalar com Cirurgião Geral', 'Visita hosp. Cirurgião Geral', 1, '00.02.043-5'), 
  ('Visita hospitalar com Cirurgião Endocrinológico', 'Visita hosp. Cirurgião Endocrinológico', 1, '00.02.044-3'), 
  ('Visita hospitalar com Ginecologista e Obstétra', 'Visita hosp. Ginecologista e Obstétra', 1, '00.02.045-1'), 
  ('Visita hospitalar com Especialista em Microcirurgia Reconstrutiva', 'Visita hosp. Especialista em Microcirurgia Reconst', 1, '00.02.046-0'), 
  ('Visita hospitalar com Mastologista', 'Visita hosp. Mastologista', 1, '00.02.047-8'), 
  ('Visita hospitalar com Cirurgião da Mão', 'Visita hosp. Cirurgião da Mão', 1, '00.02.048-6'), 
  ('Visita hospitalar com Neurocirurgião', 'Visita hosp. Neurocirurgião', 1, '00.02.049-4'), 
  ('Visita hospitalar com Oftalmologista', 'Visita hosp. Oftalmologista', 1, '00.02.050-8'), 
  ('Visita hospitalar com Otorrinolaringologista', 'Visita hosp. Otorrinolaringologista', 1, '00.02.051-6'), 
  ('Visita hospitalar com Ortopedista', 'Visita hosp. Ortopedista', 1, '00.02.052-4'), 
  ('Visita hospitalar com Cirurgião Pediátrico', 'Visita hosp. Cirurgião Pediátrico', 1, '00.02.053-2'), 
  ('Visita hospitalar com Cirurgião Plástico', 'Visita hosp. Cirurgião Plástico', 1, '00.02.054-0'), 
  ('Visita hospitalar com Cirurgião Torácico', 'Visita hosp. Cirurgião Torácico', 1, '00.02.055-9'), 
  ('Visita hospitalar com Urologista', 'Visita hosp. Urologista', 1, '00.02.056-7'), 
  ('Visita hospitalar com Pediatra', 'Visita hosp. Pediatra', 1, '00.02.070-2'), 
  ('Visita hospitalar com Psiquiatra', 'Visita hosp. Psiquiatra', 1, '00.02.072-9'), 
  ('Visita hospitalar com Endocrinologista', 'Visita hosp. Endocrinologista', 1, '00.02.073-7'), 
  ('Visita hospitalar com Geriatra', 'Visita hosp. Geriatra', 1, '00.02.074-5'), 
  ('Visita hospitalar com Infectologista', 'Visita hosp. Infectologista', 1, '00.02.075-3'), 
  ('Visita hospitalar com Neurologista', 'Visita hosp. Neurologista', 1, '00.02.076-1'), 
  ('Visita hospitalar com Cirurgião do Aparelho Digestivo', 'Visita hosp. Cirurgião do Aparelho Digestivo', 1, '00.02.094-0'), 
  ('Visita hospitalar com Proctologista', 'Visita hosp. Proctologista', 1, '00.02.095-8')
GO
COMMIT
GO

Update dbo.tbl_especialidade Set sn_ativo = 0;
Update dbo.tbl_especialidade Set sn_ativo = 1 where nr_tuss in ('00.01.029-4', '00.01.073-1'); 
