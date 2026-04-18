



CREATE table llx_colabor(
	rowid				integer AUTO_INCREMENT PRIMARY KEY, -- clé principale
	ref					varchar(32) NULL DEFAULT '',	-- ref du colabor
	fk_user_create		integer NOT NULL DEFAULT 0,				-- destinataire du colabor
	fk_user_modif		integer  NULL DEFAULT 0,			-- créateur du colabor
	fk_element			integer  NOt NULL DEFAULT 0,			-- clé de l'élément
	fk_ref				varchar(32) NULL DEFAULT '',	-- ref du document
	elementtype			varchar(32) NULL DEFAULT '',			-- type de l'élément
	description			text,							-- description initiale du colabor
	descriptionedit		text,							-- réponse faite au colabor
	datec				datetime,						-- date de réponse du colabor
	datee				datetime,						-- date de cloture du colabor
	state  				integer DEFAULT 1 NOT NULL,		-- cacher la discussion terminée (0 visible, 1 supprimé)
	fk_parent			integer DEFAULT 0 NOT NULL,		-- reminder principal si arbo
	entity				integer DEFAULT 1 NOT NULL,		-- multi company id
	import_key			varchar(14)      	-- import key
)ENGINE=innodb;



