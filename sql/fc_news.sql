-- 新闻公告表
-- 由 php_flamecloud 后台配置内容，go_flamecloud 提供公开读接口（/v1/news/list、/v1/news/detail），
-- vue_flamecloud 与 flutter_flamecloud 首页展示。content 字段存储富文本 HTML。
CREATE TABLE IF NOT EXISTS `fc_news` (
  `id`          int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键',
  `title`       varchar(200) NOT NULL DEFAULT '' COMMENT '标题',
  `summary`     varchar(500) NOT NULL DEFAULT '' COMMENT '摘要',
  `cover_image` varchar(500) NOT NULL DEFAULT '' COMMENT '封面图',
  `content`     text COMMENT '富文本内容(HTML)',
  `source`      varchar(100) NOT NULL DEFAULT '' COMMENT '来源',
  `author`      varchar(50)  NOT NULL DEFAULT '' COMMENT '发布人',
  `status`      tinyint(1)   NOT NULL DEFAULT 1 COMMENT '状态 0=隐藏 1=显示',
  `sort`        int(11)      NOT NULL DEFAULT 0 COMMENT '排序',
  `publish_time` datetime     DEFAULT NULL COMMENT '发布时间',
  `create_time` datetime     DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime     DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_status_publish` (`status`, `publish_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='新闻公告';
