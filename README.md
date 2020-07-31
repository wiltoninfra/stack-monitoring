![AWSCodeBuild](https://codebuild.us-east-1.amazonaws.com/badges?uuid=eyJlbmNyeXB0ZWREYXRhIjoiYU9KUzBCUVVpMlREd3U3M2FuWUdkbFRUTmlpZzNhUElCNHA1REE0Q3F0S1BRZXNMS2YxKzNrcnlsYkg0Q1Q3M0hudWxEdmRQbWdnVHRHNTg1L1JzRWZ3PSIsIml2UGFyYW1ldGVyU3BlYyI6ImhuNG5vZkNhbDVicVQ5ai8iLCJtYXRlcmlhbFNldFNlcmlhbCI6MX0%3D&branch=master) 

# Sumário
- [Sobre](#Sobre)
- [Dependências](#Dependências)
- [Codeowners](#Codeowners)
- [Deploy](#Deploy)


## Sobre
Gerenciamento de campanhas, incluindo as que permitem cashback

## Requisitos
- Habilitar acesso ao Github para baixar as libs da common
Seguir os passos descritos na [therad](https://picpay.slack.com/archives/CDMB57Q3C/p1569000201050900) para que o build do promo consiga baixar as libs da common.

#### Dependências
- Legacy - https://github.com/PicPay/picpay-dev-ms-legacy
- Reward - https://github.com/PicPay/picpay-dev-ms-reward

## Codeowners
 - [Codeowners](https://github.com/PicPay/picpay-dev-ms-promo/blob/master/CODEOWNERS)

## Deploy
### Local

- Faça uma copia do `.env.example` para `.env`
```sh
cp api/.env.example api/.env
```

- Execute o docker-compose
```sh
docker-compose up -d --build 
```

- Instale as dependência do php/composer.
```sh
docker exec -it api.promo.dev composer install
```

| Serviço  | Porta | URL-Local |
|---|---|---|
| api.promo.dev | 9040 | http://localhost:9040/ |
| mongo.promo.dev | 27040 |---|

- Swagger

```
http://localhost:9040/api/documentation
```
- Xdebug 
    - Seguir os passos no [tutorial](https://picpay.atlassian.net/l/c/3A3wU5FL) para configuração do Xdebug.

### QA
```
/ms deploy promo qa
```
### Prod
```
/ms deploy promo prod
```


## Load Teste
wrk -t4 -c100 http://localhost:9041
10195  wrk -t4 -c100 http://localhost:9040
10196  wrk -t4 -c100 http://localhost:9041/api/documentation
10197  wrk -t10 -c100 http://localhost:9041/api/documentation
10198  wrk -t10 -c100 http://localhost:9040/api/documentation
10199  wrk -t10 -c100 -d10s  http://localhost:9040/api/documentation
10200  wrk -t10 -c100 -d10s  http://localhost:9041/api/documentation



docker run \
  --net $TEST_NET --ip $CLIENT_IP \
  -v "${volume_path}":${jmeter_path} \
  --rm \
  jmeter \
  -n -X \
  -Jclient.rmi.localport=7000 \
  -R $(echo $(printf ",%s" "${SERVER_IPS[@]}") | cut -c 2-) \
  -t ${jmeter_path}/<jmx_script> \
  -l ${jmeter_path}/client/result_${timestamp}.jtl \
  -j ${jmeter_path}/client/jmeter_${timestamp}.log