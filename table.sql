
CREATE TABLE `fb_step` (
  `id` int(11) UNSIGNED NOT NULL,
  `project` int(11) NOT NULL,
  `platform` tinyint(11) NOT NULL COMMENT '1-vk, 2-telegram',
  `user_id` int(11) UNSIGNED NOT NULL,
  `controller` varchar(255) NOT NULL,
  `params` blob,
  `time` int(11) NOT NULL
);

ALTER TABLE `fb_step`
  ADD PRIMARY KEY (`id`),
  ADD KEY `platform` (`platform`,`project`,`user_id`);

ALTER TABLE `fb_step`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;


