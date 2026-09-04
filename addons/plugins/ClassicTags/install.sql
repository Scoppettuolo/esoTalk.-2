-- ClassicTags schema for esoTalk 2.0.3
-- If esoTalk.database.prefix is not empty, prepend that prefix to `conversation_tag`.
CREATE TABLE IF NOT EXISTS `conversation_tag` (
  `conversationId` int(11) unsigned NOT NULL,
  `tag` varchar(80) NOT NULL,
  `createdBy` int(11) unsigned DEFAULT NULL,
  `time` int(11) unsigned NOT NULL,
  PRIMARY KEY (`conversationId`, `tag`),
  KEY `conversation_tag_tag_conversationId` (`tag`, `conversationId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
