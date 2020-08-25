#!/bin/bash

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Primer parametro siempre ha de ser el target host.
if [ $# -gt 0 ]; then
	host=$1
else
	read -p "Indique el Host: " host
fi

#Segundo parametro ha de ser la database a clonar.
if [ $# -gt 1 ]; then
	database=$2
else
	read -p "Indique el nombre de la base de datos: " database
fi

#Tercer parametro ha de ser la database local.
if [ $# -gt 2 ]; then
	databaseLocal=$3
else
	read -p "Indique el nombre de la base de datos local (opcional): " databaseLocal
fi

#Si no tuvimos el tercer parametro, entonces el nombre de la base de datos local será igual a la remota.
if [ -z "$databaseLocal" ]; then
	databaseLocal=$database
fi

echo -e "Clonando database '${YELLOW}${database}${NC}' desde host '${YELLOW}${host}${NC}' en database local '${YELLOW}${databaseLocal}${NC}'"

#Intentamos determinar la password de la base de datos en el host remoto.
echo "Determinando password de Database en Host"
dbpass=$(ssh ${host} "{ mysql -e \"select 1\" >/dev/null 2>&1 || echo \"-p\"; } || echo \"\" ")

if [ "${dbpass}" == "-p" ]; then
	echo -e "${YELLOW}Base de datos requiere contraseña${NC}"
	read -s -p "Password de database en ${host}: " dbpass
	dbpass="-p${dbpass}"
	echo ""
fi

echo -e "Conectando a '${YELLOW}${host}${NC}', recuperando Database: '${YELLOW}${database}${NC}' y comprimiendo"
echo "Esto puede tardar un poco dependiendo del tamaño de la base de datos y el servidor..."
ssh ${host} "mysqldump ${dbpass} --lock-tables=false ${database} 2>/dev/null | sed -e 's/DEFINER[ ]*=[ ]*[^*]*\*/\*/' > ${database}.sql && tar -czf ${database}.sql.tar.gz ${database}.sql"

if [ $? -ne 0 ]; then
    echo "Error al intentar generar el respaldo de database en ${host}. Verifique que la base de datos '${database}' exista."
	read -p ""
	exit 1
fi

echo "Copiando el archivo resultante..."
scp ${host}:~/${database}.sql.tar.gz .

if [ $? -ne 0 ]; then
    echo "Error al intentar copiar el archivo de respaldo. Verifique que tenga acceso y haya espacio en el servidor remoto."
	read -p ""
	exit 1
fi

echo -e "Eliminando archivos: '${YELLOW}${host}:${database}.sql${NC}' y '${YELLOW}${host}:${database}.sql.tar.gz${NC}'"
ssh ${host} "rm -rf ${database}.sql ${database}.sql.tar.gz"

echo "Descomprimiendo archivo descargado..."
tar -xf ${database}.sql.tar.gz
rm -rf ${database}.sql.tar.gz

echo "Cargando nueva database en local"
mysql -e "drop database ${databaseLocal};" 2>/dev/null
mysql -e "create database ${databaseLocal};"
pv ${database}.sql | mysql ${databaseLocal}


echo -e "${GREEN}Listo!${NC} Presione cualquier tecla para terminar."
read -p ""
exit 0
