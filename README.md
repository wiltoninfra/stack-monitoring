
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