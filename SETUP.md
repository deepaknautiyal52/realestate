# Real Estate site — running it on another computer

WordPress 7.1.2 · Astra 4.14 · Elementor 4.3 · custom plugin `wp-content/plugins/realestate-core`.
The database is exported in `database/realestate.sql`.

## Set up on a new laptop (XAMPP)

1. Clone into XAMPP's `htdocs` folder **with the same folder name**:
   ```
   cd C:\xampp\htdocs
   git clone <repo-url> realestate
   ```
2. Start Apache and MySQL in the XAMPP control panel.
3. Create the database: open http://localhost/phpmyadmin → **New** → name `realestate`,
   collation `utf8mb4_unicode_ci` → Create. Then **Import** → choose `database/realestate.sql` → Import.
4. Create `wp-config.php`: copy `wp-config-sample.php` to `wp-config.php` and set
   ```php
   define( 'DB_NAME', 'realestate' );
   define( 'DB_USER', 'root' );
   define( 'DB_PASSWORD', '' );
   define( 'DB_HOST', 'localhost' );
   ```
   and replace the "put your unique phrase here" lines with fresh keys from
   https://api.wordpress.org/secret-key/1.1/salt/
5. Open http://localhost/realestate/ — log in at http://localhost/realestate/wp-admin/ with the same admin account.
6. If pages show "Not Found", go to **Settings → Permalinks** and click **Save Changes** once.

### Different folder name or URL?

The database stores `http://localhost/realestate`. If you use another folder name (or later a live domain),
replace it everywhere with WP-CLI:
```
wp search-replace "http://localhost/realestate" "http://localhost/NEW-FOLDER" --all-tables
```
and update `RewriteBase` / the last `RewriteRule` in `.htaccess` to the new folder.

## Saving database changes back to GitHub

Content (properties, pages, settings) lives in the database, not in files. After making changes, re-export
before committing:
```
C:\xampp\mysql\bin\mysqldump.exe -u root --single-transaction --skip-dump-date realestate --result-file=database\realestate.sql
git add -A && git commit -m "Update content" && git push
```
Only one computer should edit content at a time; importing the dump on another machine replaces its database.

## Notes

- `wp-content/mu-plugins/local-ssl-fix.php` fixes plugin/theme downloads on networks that inspect HTTPS.
  It points to `C:/xampp2/php/cacert.pem`; on a laptop with XAMPP in `C:\xampp`, change that path to
  `C:/xampp/php/cacert.pem` if installs fail with "cURL error 60". It does nothing where the file doesn't exist.
- WordPress needs the PHP `gd` extension enabled in `php.ini` (`extension=gd`) for image resizing.
