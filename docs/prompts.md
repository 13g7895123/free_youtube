1. 幫我確認一下，目前前端專案打後端api的部分似乎沒有寫入.env.prod.example中，幫我更新進去，並確保前端專案打的是設定檔中的路徑
2. 幫我看一下這個後端專案，資料庫連線怎麼連線的，請用zh-tw回復
3. 為甚麼不是用CI4的database進行連線，幫我修改，另外，他這樣是不是沒有吃到最外層的db連線資訊設定
4. 承3，目前後端專案的db設定是否與專案根目錄的.env.prod相匹配，我目前看是用php原生的啟動，不能用spark嗎
5. 承4，production的docker-compose.yml不用更新嗎
6. 報了以下錯誤，
This "system/bootstrap.php" is no longer used. If you are seeing this error message,
the upgrade is not complete. Please refer to the upgrade guide and complete the upgrade.
See https://codeigniter4.github.io/userguide/installation/upgrade_450.html

Warning: require_once(/var/www/html/app/Config/Autoload.php): Failed to open stream: No such file or directory in /var/www/html/vendor/codeigniter4/framework/system/bootstrap.php on line 106

Fatal error: Uncaught Error: Failed opening required '/var/www/html/app/Config/Autoload.php' (include_path='.:/usr/local/lib/php') in /var/www/html/vendor/codeigniter4/framework/system/bootstrap.php:106
Stack trace:
#0 /var/www/html/spark(51): require()
#1 {main}
  thrown in /var/www/html/vendor/codeigniter4/framework/system/bootstrap.php on line 106