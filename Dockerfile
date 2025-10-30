# 使用 nginx 作為生產環境的 web 服務器
FROM nginx:alpine

# 移除 nginx 預設的配置和內容
RUN rm -rf /usr/share/nginx/html/*
RUN rm /etc/nginx/conf.d/default.conf

# 複製構建後的靜態文件到 nginx 的服務目錄
COPY frontend/dist/ /usr/share/nginx/html/

# 複製自定義的 nginx 配置
COPY nginx.conf /etc/nginx/conf.d/default.conf

# 暴露 80 端口（容器內部）
EXPOSE 80

# 啟動 nginx
CMD ["nginx", "-g", "daemon off;"]
