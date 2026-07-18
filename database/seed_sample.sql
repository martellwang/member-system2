USE `member_system`;

UPDATE `members`
SET `name` = '王小明', `company_name` = NULL
WHERE `email` = 'ming@mail.com';

UPDATE `members`
SET `name` = '林美華', `company_name` = NULL
WHERE `email` = 'hua@mail.com';

UPDATE `members`
SET `name` = '張志豪', `company_name` = NULL
WHERE `email` = 'hao@mail.com';

UPDATE `members`
SET `name` = '陳大文', `company_name` = '科技股份有限公司'
WHERE `email` = 'admin@techco.com';

UPDATE `members`
SET `name` = '劉資訊', `company_name` = '資訊軟體有限公司'
WHERE `email` = 'info@infosoft.com';

UPDATE `members`
SET `name` = '黃貿易', `company_name` = '全球貿易企業社'
WHERE `email` = 'biz@trade.com';
