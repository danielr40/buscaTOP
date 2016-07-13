# Componente extrator
# Autores: Daniel Reis e Lucas Viana 

import time 
import re
import pymongo
from pymongo import MongoClient
from unicodedata import normalize
from nltk.corpus import stopwords
from nltk.stem import SnowballStemmer 

rmSpecChar = re.compile('[^a-zA-Z]')
patternParagrafos = re.compile("<p>(.*)</p>")
patternTexto = re.compile("(([^<]*)<[^>]*>)")
patternTitulo = re.compile('<title>([^<]*)</title>')
patternDivCategorias = re.compile('<div id="mw-normal-catlinks" class="mw-normal-catlinks">(.*)</div><di')
patternCategorias = re.compile('<a href="\/wiki\/C[^>]*>([^<]*)<\/a>')

stopWordsPt = stopwords.words('portuguese')
stemmer = SnowballStemmer("portuguese")

def stemTexto(txt):
        txt = txt.split()
        for i in range(0,len(txt)):
                txt[i]=stemmer.stem(txt[i])
        return " ".join(txt)

def limparTexto(txt):
        # converter todos os caracteres para caixa baixa
        txt = txt.lower();
        # remover stop words 
        txt = " ".join([i for i in txt.split() if i not in stopWordsPt])
        # remover acentos 
        txt = normalize('NFKD', txt).encode('ASCII','ignore').decode('ASCII')
        # remover caracteres especiais e numeros
        txt = re.sub(rmSpecChar,' ',txt)
        # remover stop words 
        txt = " ".join([i for i in txt.split() if i not in stopWordsPt])
        # remover espacos em branco
        # txt = " ".join(txt.split())
        # fazer stemmizacao
        txt = stemTexto(txt)
        return txt.encode('utf-8','ignore').decode('utf-8')

def limpaCategoria(txt):
        # converter todos os caracteres para caixa baixa
        txt = txt.lower();
        # remover acentos 
        txt = normalize('NFKD', txt).encode('ASCII','ignore').decode('ASCII')
        return txt 

def extrator():
        client = MongoClient()
        db = client.documentos
        urls = db.urls
        
        inicio = time.clock()
        contador = 0 

        for url in urls.find({"titulo":{"$exists":False}}): # para cada url nao processada
                try:
                        # abrir o arquivo .html e extrair seu conteudo para uma string
                        arq = url["dir"]+"\\"+url["doc"]
                        html = open(arq,'r',encoding="utf-8").read()
                        texto = '';
                        # extrair o titulo da pagina
                        titulo = '';
                        ti = re.search(patternTitulo,html)
                        if ti is not None:
                                titulo = ti.group(1)
                        # print("titulo: "+titulo)
                        # extrair o conteudo relevanteda pagina
                        descr = ''
                        paragrafos = re.findall(patternParagrafos,html)
                        for p in paragrafos:
                                m = re.findall(patternTexto,p)
                                for i in m:
                                        texto+=i[1]
                        descr = texto[:200].strip()+"..."
                        texto = limparTexto(texto)
                        #print("descrição:" +descr)
                        #print("texto:" +texto)
                        # extrair as categorias
                        categorias = [] 
                        divCat = re.search(patternDivCategorias,html)
                        if divCat is not None:
                                categorias = re.findall(patternCategorias,divCat.group(1))
                                #categorias = limparTexto(" ".join(categorias)).split()
                                for i in range(0,len(categorias)):
                                        categorias[i]=limpaCategoria(categorias[i])
                                #print(categorias)
                        # atualizar registro no banco de dados
                        result = urls.update_one(
                                        {"_id":url["_id"]},
                                                {
                                                "$set": {
                                                 "titulo":titulo,
                                                 "descricao":descr,
                                                 "texto":texto,
                                                 "categorias":categorias,
                                                 "length":len(texto)
                                                },
                                                "$currentDate":{"lastModified":True}
                                                }
                                        )
                        tempoDecorrido = time.clock()-inicio
                        contador+=1
                        print(str(tempoDecorrido))
                        #print()
                except:
                        pass
                        #print("erro!");
                        #print()
                

extrator()
input()        
