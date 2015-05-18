# Cool SQL queries #

## Selecting the top notch actors (Warning: this takes some time) ##
SELECT person.id, person.name, AVG(production.rating) FROM `person` INNER JOIN acting ON acting.idPerson = person.id INNER JOIN production ON production.id = acting.idProduction GROUP BY(person.id) HAVING COUNT(acting.id) > 5 ORDER BY AVG(production.rating) DESC LIMIT 0,30