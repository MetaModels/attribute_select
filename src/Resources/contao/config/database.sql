-- **********************************************************
-- *                                                        *
-- * IMPORTANT NOTE                                         *
-- *                                                        *
-- * Do not import this file manually but use the Contao    *
-- * install tool to create and maintain database tables!   *
-- *                                                        *
-- **********************************************************

-- 
-- Table `tl_metamodel_attribute`
-- 

CREATE TABLE `tl_metamodel_attribute` (
  `select_table` varchar(255) NOT NULL default '',
  `select_column` varchar(255) NOT NULL default '',
  `select_id` varchar(255) NOT NULL default '',
  `select_alias` varchar(255) NOT NULL default '',
  `select_sorting` varchar(255) NOT NULL default '',
  `select_where` text NULL,
  `select_filter` int(11) unsigned NOT NULL default '0',
  `select_filterparams` text NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Table `tl_metamodel_dcasetting`
-- 

CREATE TABLE `tl_metamodel_dcasetting` (
  `select_as_radio` varchar(1) NOT NULL default '0'
  `select_minLevel` int(11) NOT NULL default '0',
  `select_maxLevel` int(11) NOT NULL default '0'
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
