#!/bin/bash
export DIR="/var/ossec/rules"
export TDIR="/tmp/ossec_rules"
mkdir ${TDIR}
cd ${DIR}
for i in `ls *.xml`
do
   echo $i
   echo '<ossec>' > ${TDIR}/${i}
   grep -v '<var ' ${i} >>  ${TDIR}/${i}
   echo '</ossec>' >> ${TDIR}/${i}
done

