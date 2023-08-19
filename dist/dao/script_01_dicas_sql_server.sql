-- Gerar Lista de Dias
declare @startDate date;
declare @endDate date;

select @startDate = '20150701';
select @endDate = '20150705';

with dateRange as
(
  select dt = @startDate
  where @startDate < @endDate
  union all
  select dateadd(dd, 1, dt)
  from dateRange
  where dateadd(dd, 1, dt) <= @endDate
)
select convert(varchar(MAX),dt,103)
from dateRange

-- Gerar Intervalo de Horários
declare @HoraIni time;
declare @HoraFim time;
declare @Intervalo integer;

set @HoraIni = '08:00';
set @HoraFim = '12:00';
set @Intervalo = 15; -- 15 minutos

with CTE_H as
(
    select @HoraIni as Horario
    
    union all
    
    select DATEADD(MINUTE, @Intervalo, Horario)
    from CTE_H
    where DATEADD(MINUTE, @Intervalo, Horario) <= @HoraFim
)

select * from CTE_H