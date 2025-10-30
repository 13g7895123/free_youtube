1. 幫我確認一下，目前前端專案打後端api的部分似乎沒有寫入.env.prod.example中，幫我更新進去，並確保前端專案打的是設定檔中的路徑
2. 幫我看一下這個後端專案，資料庫連線怎麼連線的，請用zh-tw回復
3. 為甚麼不是用CI4的database進行連線，幫我修改，另外，他這樣是不是沒有吃到最外層的db連線資訊設定
4. 承3，目前後端專案的db設定是否與專案根目錄的.env.prod相匹配，我目前看是用php原生的啟動，不能用spark嗎
5. 承4，production的docker-compose.yml不用更新嗎
6. 報了以下錯誤，
nginx: [emerg] host not found in upstream "backend" in /etc/nginx/conf.d/default.conf:50
/docker-entrypoint.sh: /docker-entrypoint.d/ is not empty, will attempt to perform configuration
/docker-entrypoint.sh: Looking for shell scripts in /docker-entrypoint.d/
/docker-entrypoint.sh: Launching /docker-entrypoint.d/10-listen-on-ipv6-by-default.sh
10-listen-on-ipv6-by-default.sh: info: Getting the checksum of /etc/nginx/conf.d/default.conf
10-listen-on-ipv6-by-default.sh: info: /etc/nginx/conf.d/default.conf differs from the packaged version
/docker-entrypoint.sh: Sourcing /docker-entrypoint.d/15-local-resolvers.envsh
/docker-entrypoint.sh: Launching /docker-entrypoint.d/20-envsubst-on-templates.sh
/docker-entrypoint.sh: Launching /docker-entrypoint.d/30-tune-worker-processes.sh
/docker-entrypoint.sh: Configuration complete; ready for start up
2025/10/30 13:57:56 [emerg] 1#1: host not found in upstream "backend" in /etc/nginx/conf.d/default.conf:50
nginx: [emerg] host not found in upstream "backend" in /etc/nginx/conf.d/default.conf:50