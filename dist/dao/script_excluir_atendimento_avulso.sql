Delete
from dbo.tbl_agenda 
where dt_agenda = '2021-04-16'
  and sn_avulso = 1 -- Atendimento avulso
  and st_agenda = 4 -- Marcado como cancelado
  and id_agenda  = '{80DF180C-73E5-4B57-8C25-EA8E74664DA6}'
  and id_empresa = '{338A525E-4F71-47DC-8DD8-835103BEDEBB}'
