# Laravel Data Sync

Laravel utility to keep records synced between enviroments through source control

- Add new `sync` disk in `config/filesystems.php`
- Create a JSON file for each model, using the model name as the filename. Example: Product.json would update the Product model
- Use nested arrays in place of hardcoded IDs for relationships
- Run `php artisan data:sync`


## Examples
**User.json**:
```json
[
    {
        "name": "Ferris Bueller",
        "properties->title": "Leisure Consultant",
        "phone_numbers->mobile": "555-555-5555",
        "phone_numbers->office": "", // empty values are skipped
        "_email": "ferris@buellerandco.com", // the criteria/attributes for updateOrCreate are identified with a preleading underscore
        "department": { // nested values represent relationships and are returned using where($key, $value)->first()
            "name": "Management",
            "location": {
                "name": "Chicago"
            }
        }
    }
]
```

translates to:

```php

User::updateOrCreate([
    'email' => 'ferris@buellerandco.com',
],[
	'name': 'Ferris Bueller',
    'properties->title' => 'Leisure Consultant',
    'phone_numbers->mobile' => '555-555-5555',
    'department_id' => Department::where('name', 'Management)
                        ->where('location_id', Location::where('name', 'Chicago')->first()->id)
                        ->first()
                        ->id,
]);

```