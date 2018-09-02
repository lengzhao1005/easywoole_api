CREATE TABLE IF NOT EXISTS `user` (
  `id_user` INT (11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `email` VARCHAR (50) NULL COMMENT '邮箱',
  `phone` VARCHAR (30) NULL COMMENT '手机号',
  `password` VARCHAR (100) NOT NULL COMMENT '密码',
  `avatar` VARCHAR (100) DEFAULT '' COMMENT '头像',
  `nickname` VARCHAR (100) DEFAULT '' COMMENT '昵称',
  `token` VARCHAR (60) NULL COMMENT 'token',
  `create_time` TIMESTAMP NULL COMMENT '创建时间',
  `update_time` TIMESTAMP NULL COMMENT '更新时间',
  PRIMARY KEY (`id_user`),
  UNIQUE `email_name` (`email`),
  UNIQUE `phone_name` (`phone`),
  UNIQUE `token_name` (`token`),
  INDEX `create_time_name` (`create_time`),
  INDEX `update_time_name` (`update_time`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '用户表';

CREATE TABLE IF NOT EXISTS `wx_user` (
  `id_wx_user` INT (11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `id_user` INT (11) DEFAULT '0' COMMENT 'id_user',
  `openid` VARCHAR (60) DEFAULT '' COMMENT '微信openid',
  `unionid` VARCHAR (60) DEFAULT '' COMMENT '微信unionid',
  `avatar` VARCHAR (100) DEFAULT '' COMMENT '头像',
  `nickname` VARCHAR (100) DEFAULT '' COMMENT '昵称',
  `sex` TINYINT (2) DEFAULT '0' COMMENT '性别：1男；2女；0未知',
  `country` VARCHAR (60) NULL COMMENT '国家',
  `province` VARCHAR (60) NULL COMMENT '省份',
  `city` VARCHAR (60) NULL COMMENT '城市',
  `language` VARCHAR (60) NULL COMMENT '语言',
  `create_time` TIMESTAMP NULL COMMENT '创建时间',
  `update_time` TIMESTAMP NULL COMMENT '更新时间',
  PRIMARY KEY (`id_wx_user`),
  UNIQUE `openid_name` (`openid`),
  UNIQUE `unionid_name` (`unionid`),
  INDEX `id_user_name` (`id_user`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '微信用户信息';

CREATE TABLE IF NOT EXISTS `project` (
  `id_project` INT (11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` VARCHAR (60) NOT NULL COMMENT '项目名称',
  `subordinate` ENUM ('private', 'protect', 'public') DEFAULT 'public' NOT NULL COMMENT 'private:私有；protect：团队；public:公有',
  `id_user_create` INT (11) NOT NULL COMMENT '项目创建人',
  `create_time` TIMESTAMP NULL COMMENT '创建时间',
  `update_time` TIMESTAMP NULL COMMENT '更新时间',
  PRIMARY KEY (`id_project`),
  UNIQUE `name_name` (`name`),
  INDEX `id_user_create_name` (`id_user_create`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '项目表';

CREATE TABLE IF NOT EXISTS `subproject` (
  `id_subproject` INT (11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `name` VARCHAR (60) NOT NULL COMMENT '项目名称',
  `id_project` INT (11) NOT NULL COMMENT '父项目',
  `id_user_create` INT (11) NOT NULL COMMENT '子项目创建人',
  `create_time` TIMESTAMP NULL COMMENT '创建时间',
  `update_time` TIMESTAMP NULL COMMENT '更新时间',
  PRIMARY KEY (`id_subproject`),
  INDEX `name_name` (`name`),
  INDEX `id_user_create_name` (`id_user_create`),
  INDEX `id_project_name` (`id_project`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '子项目表';

CREATE TABLE IF NOT EXISTS `task` (
  `id_task` INT (11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `content` text (2000) NOT NULL COMMENT '任务内容',
  `emergency_rank` ENUM ('low', 'middle', 'high') NOT NULL COMMENT '任务紧急度',
  `cost_time` VARCHAR (100) NULL COMMENT '预计用时/单位：小时',
  `expire_time` TIMESTAMP NULL COMMENT '到期时间',
  `id_user_create` INT (11) NOT NULL COMMENT '任务创建人',
  `id_user_confirm` INT (11) DEFAULT '0' COMMENT '任务确认人',
  `id_user_assign` INT (11) DEFAULT '0' COMMENT '任务布置人',
  `create_time` TIMESTAMP NULL COMMENT '创建时间',
  `update_time` TIMESTAMP NULL COMMENT '更新时间',
  PRIMARY KEY (`id_task`),
  INDEX `id_user_create_name` (`id_user_create`),
  INDEX `id_user_confirm_name` (`id_user_confirm`),
  INDEX `id_user_assign_name` (`id_user_assign`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '任务表';

CREATE TABLE IF NOT EXISTS `task_user` (
  `id_task_user` INT (11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `id_task` INT (11) NOT NULL COMMENT 'id_task',
  `id_user` INT (11) NOT NULL COMMENT 'id_user',
  `create_time` TIMESTAMP NULL COMMENT '创建时间',
  `update_time` TIMESTAMP NULL COMMENT '更新时间',
  PRIMARY KEY (`id_task_user`),
  INDEX `id_task_name` (`id_task`),
  INDEX `id_user_name` (`id_user`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '任务-用户表';

CREATE TABLE IF NOT EXISTS `project_user` (
  `id_project_user` INT (11) NOT NULL AUTO_INCREMENT COMMENT 'id',
  `id_project` INT (11) NOT NULL COMMENT 'id_project',
  `id_user` INT (11) NOT NULL COMMENT 'id_user',
  `create_time` TIMESTAMP NULL COMMENT '创建时间',
  `update_time` TIMESTAMP NULL COMMENT '更新时间',
  PRIMARY KEY (`id_project_user`),
  INDEX `id_task_name` (`id_project`),
  INDEX `id_user_name` (`id_user`)
)ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '项目-用户表';