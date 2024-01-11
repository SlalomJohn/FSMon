### What's this

Script allow monitor changes to files in specified directories.
When changes detected, it'll notify administrator by email, telegram, write logs
this changes.

### Configure

See and set options in `config.php`

### First run
php /FULL_PATH/fsmon.php

### Setup

Make sure `.cache` file php-writable
```
touch .cache
chmod g+w ./.cache
```

### Run task with cron

```
crontab -e
0 	3 	* 	* 	* 	/usr/bin/env php -f /FULL_PATH/fsmon.php >> /FULL_PATH/fsmon.log 2>&1 
```


### Описание на русском

Скрипт мониторинга файловой системы с информированием пользователя о прошедших изменениях.
Использовать скрипт полезно запуская задание по крону (например ночью, в 3 часа)

```
crontab -e
0   3   *   *   *   /usr/bin/env php -f /FULL_PATH/fsmon.php >> /FULL_PATH/fsmon.log 2>&1 
```

в случае выявления изменений, отправит сформированный отчет администратору.

### Настройки
смотрите файл конфигурации config.php

### Скачать скрипт (github)
https://github.com/SlalomJohn/FSMon

### Проверить можно командой
php fsmon.php

После первого запуска скрипт создаст базу с контрольными суммами файлов в файле .cache (заранее поставьте права на запись!).

При включенной опции отправки на почту поступит отчет вида
[  modified]    /misc/fs_monitor/fsmon.php         5.1 kb  28.09.2009 23:37
[       new]    /misc/fs_monitor/1/sc.phps       1 kb    28.09.2009 23:09
[   deleted]    /misc/fs_monitor/1/op.phps        2 kb    01.01.1970 03:00

А так же при включенной опции отправки в телеграм придет уведомление и файл с отчетом.

Скрипт поможет решить следующие вопрос:
Как отследить изменения файлов на сервере. мониторинг изменения страниц сайта.
Защита от взлома сайта.

Описание на русском оригинальной версии (http://www.skillz.ru/dev/php/article-Skript_monitoringa_izmenenii_faylov.html)
