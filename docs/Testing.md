```bash
sdc up -d database manticoresearch-manticore phpcli
sdc exec phpcli /bin/bash
```

then

```bash
mariadb --host=database --user=root --password=root66
```

then

```sql
GRANT ALL PRIVILEGES ON *.* TO 'docker'@'%';
```
