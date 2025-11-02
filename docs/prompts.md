CREATE TABLE notifications (
    id              BIGINT PRIMARY KEY AUTO_INCREMENT,
    project         VARCHAR(100) NOT NULL COMMENT '專案名稱，例如: crm、backend、game_bot',
    title           VARCHAR(100) NOT NULL COMMENT '通知標題',
    message         TEXT NOT NULL COMMENT '通知內容',
    status          TINYINT NOT NULL DEFAULT 0 COMMENT '0=未通知, 1=已通知',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    notified_at     DATETIME NULL COMMENT '實際通知時間'
);

上方有一個database的table建立sql，幫我寫一支api，不需要驗證，可以在該表單建立資料，資料需要驗證，然後再幫我寫一支API，一樣不需要驗證，但需要id可以更新該筆通知狀態
