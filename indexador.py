# Componente indexador
# Autores: Daniel Reis e Lucas Viana

import sys
import time
import pymongo
from pymongo import MongoClient

def indexador():
    client = MongoClient()
    db = client.documentos 
    urls = db.urls # colecao que armazena as urls coletadas 
    indice = db.indice # colecao que armazena o indice invertido 
    vocabulario = set()

    # pre-popular o vocabulario 
    for term in indice.find({},{"_id":0,"termo":1}):
        vocabulario.add(term["termo"])

    count = 0
    inicio = time.clock()
    
    tempoDecorrido = 0 
        
    for documento in urls.find({"indexado":{"$exists":False}},{"_id":1,"texto":1}).batch_size(5000):
            try:
                ocorrencias = {} # ocorrencias de cada termo dentro do documento
                termos = documento['texto'].split()
                for pos in range(0,len(termos)):
                    termo = termos[pos]
                    if termo not in ocorrencias.keys():
                        ocorrencias[termo]={}
                        ocorrencias[termo]['doc']=documento['_id']
                        ocorrencias[termo]['freq']=1 # frequencia do termo no documento
                        ocorrencias[termo]['pos']=[pos] # posicoes em que ele ocorre
                    else:
                        ocorrencias[termo]['doc']=documento['_id']
                        ocorrencias[termo]['freq']=ocorrencias[termo]['freq']+1
                        ocorrencias[termo]['pos'].append(pos)
                for termo in ocorrencias.keys():
                    if termo not in vocabulario: # acrescentar termo no vocabulário
                        vocabulario.add(termo) 
                        indice.insert({"termo":termo,
                                       "freq":1,
                                       "ocorrencias":[ocorrencias[termo]]
                                    })
                    else: # atualizar o indice
                        indice.update_one(
                            {"termo":termo},
                            {"$inc":{"freq":1},
                             "$push":{
                                 "ocorrencias":ocorrencias[termo]}
                             })
                #marcar url como indexada
                urls.update_one(
                        {"_id":documento['_id']},
                        {"$set":{"indexado":True}}
                        )

                tempoDecorrido = time.clock()-inicio
                print(str(count)+" - "+str(tempoDecorrido)+"seg")
                count=count+1
            except:
                print("Erro!")

indexador()
