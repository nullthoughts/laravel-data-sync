# Laravel Data Sync

Laravel utility to keep records synced between enviroments through source control


- Create a JSON file for each model, using the model name as the filename. Example: Product.json would update the Product model
- Use nested arrays in place of hardcoded IDs for relationships
- Run `php artisan vendor:publish --provider="distinctm\LaravelDataSync\DataSyncBaseServiceProvider" --tag="data-sync-config"` to publish config file. Specify directory for sync data files (default is a new sync directory in the project root)
- Run `php artisan data:sync`

### Optional
If using Laravel Forge, you can have the data sync run automatically on deploy. Edit your deploy script in Site -> App to include:
```
if [ -f artisan ]
then
    php artisan data:sync
    php artisan migrate --force
fi
```

## Notes
- empty values are skipped
- the criteria/attributes for updateOrCreate are identified with a preleading underscore
- nested values represent relationships and are returned using where($key, $value)->first()->id

## Examples
### User.json:
```json
[
    {
        "name": "Ferris Bueller",
        "properties->title": "Leisure Consultant",
        "phone_numbers->mobile": "555-555-5555",
        "phone_numbers->office": "",
        "_email": "ferris@buellerandco.com",
        "department": {
            "name": "Management",
            "location": {
                "name": "Chicago"
            }
        }
    }
]
```

translates to...

```php
User::updateOrCreate([
    'email' => 'ferris@buellerandco.com',
],[
    'name' => 'Ferris Bueller',
    'properties->title' => 'Leisure Consultant',
    'phone_numbers->mobile' => '555-555-5555',
    'department_id' => Department::where('name', 'Management')
                        ->where('location_id', Location::where('name', 'Chicago')->first()->id)
                        ->first()
                        ->id,
]);
```

### Role.json:
```json
[
    {
        "_slug": "update-student-records"
    },
    {
        "_slug": "borrow-ferrari"
    },
    {
        "_slug": "destroy-ferrari"
    }
]
```

translates to...

```php
    Role::updateOrCreate(['slug' => 'update-student-records']);

    Role::updateOrCreate(['slug' => 'borrow-ferrari']);

    Role::updateOrCreate(['slug' => 'destroy-ferrari']);
```

### RoleUser.json (pivot table with model):
```json
[
    {
        "_user": {
            "email": "ferris@buellerandco.com"
        },
        "_role": {
            "slug": "update-student-records"
        }
    },
    {
        "_user": {
            "email": "ferris@buellerandco.com"
        },
        "_role": {
            "slug": "borrow-ferrari"
        }
    },
    {
        "_user": {
            "email": "ferris@buellerandco.com"
        },
        "_role": {
            "slug": "destroy-ferrari"
        }
    }
]
```

translates to...

```php
    RoleUser::updateOrCreate([
        'user_id' => User::where('email', 'ferris@buellerandco.com')->first()->id,
        'role_id' => Role::where('slug', 'update-student-records')->first()->id,
    ]);

    RoleUser::updateOrCreate([
        'user_id' => User::where('email', 'ferris@buellerandco.com')->first()->id,
        'role_id' => Role::where('slug', 'borrow-ferrari')->first()->id,
    ]);

    RoleUser::updateOrCreate([
        'user_id' => User::where('email', 'ferris@buellerandco.com')->first()->id,
        'role_id' => Role::where('slug', 'destroy-ferrari')->first()->id,
    ]);

```