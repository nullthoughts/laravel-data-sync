# Laravel Data Sync

- Add new `sync` disk in `config/filesystems.php`
- Create a JSON file for each model, using the model name as the filename. Example: Product.json would update the Product model
- Use nested arrays in place of hardcoded IDs for relationships
- Run `php artisan data:sync`
