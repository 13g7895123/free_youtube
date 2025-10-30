1. 幫我確認一下，目前前端專案打後端api的部分似乎沒有寫入.env.prod.example中，幫我更新進去，並確保前端專案打的是設定檔中的路徑
2. 幫我看一下這個後端專案，資料庫連線怎麼連線的，請用zh-tw回復
3. 為甚麼不是用CI4的database進行連線，幫我修改，另外，他這樣是不是沒有吃到最外層的db連線資訊設定
4. 承3，目前後端專案的db設定是否與專案根目錄的.env.prod相匹配，我目前看是用php原生的啟動，不能用spark嗎
5. 承4，production的docker-compose.yml不用更新嗎
6. 報了以下錯誤，修正完成後，請執行測試，直到測試通過為止
Fatal error: Uncaught Error: Failed opening required '/var/www/html/app/Config/../../vendor/codeigniter4/framework/system/../../autoload.php' (include_path='.:/usr/local/lib/php') in /var/www/html/spark:39
Stack trace:
#0 {main}
  thrown in /var/www/html/spark on line 39