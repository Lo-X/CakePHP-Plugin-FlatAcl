# FlatAcl for CakePHP 2.x and PHP 5.4.x

This plugin allows you to manage flat permissions in CakePHP 2.x

## Features

*   Manage permissions
*   Manage HABTM relationship between AROs and ACOs
*   Check if one or some Requesters (AROs) can access a ACO
*   No inheritance between AROs or ACOs

## Why is it Flat ? Why isn't there any inheritance ?

Sometimes you don't need complex ACL checks. Sometimes you don't need a complex Tree of AROs that result in longer checks.

If, for example, you have Users that can belongs to one or several Groups and you want to check if the user can access some feature through at least one of his Groups, then FlatAcl is designed for you.

### E.G.:

We have several groups :

*   Administrators
*   Editors
*   Users
*   Forum Administrators
*   Forum Moderators
*   Forum Users

And we have several users that belongs to one or many groups :

1.  John [Administrators,Forum Administrators]
2.  Jim  [Editors,Forum Users]
3.  Jack [Users]

And admiting the following permissions for the Forum :

|                      | _create | _edit | _delete | _read |
|----------------------|---------|-------|---------|-------|
| Administrators       |    X    |   X   |    -    |   X   |
| Forum Administrators |    X    |   X   |    X    |   X   |
| Forum Users          |    X    |   -   |    -    |   X   |
| Editors              |    -    |   X   |    -    |   X   |
| Users                |    -    |   -   |    -    |   -   |

Then, who can create something in the Forum ? John and Jim

Can Jim create AND edit things ? Yes because he's part of two groups, each giving him one of the right to create and the other to edit.

* * * 

## Requirements

*   CakePHP 2.x+
*   PHP 5.4+
*   Not compatible with the included Acl Component and tables

## How to install

### Download FlatAcl

User the _Download as Zip_ button and paste the plugin into `app/Plugin` or clone the git repo into the `app/Plugin` folder of your project.

### Enable the Plugin

In `app/Config/bootstrap.php`, enable FlatAcl or all Plugins :

```php 
    CakePlugin::load('FlatAcl');
    // or
    CakePlugin::loadAll();
```

### Database

Use the `app/Plugins/FlatAcl/Config/Schema/` files to create the required tables (`aros`, `acos` and `acos_aros`)

### Setup AppController

Open `app/Controller/AppController.php` and add the `FlatAclComponent` to the list of Components :

```php 
    class AppController extends Controller {
    
        public $components = [
            'FlatAcl.FlatAcl',
            <Whatever ther component you need>
        ];
    
        ...
    }
```

### Fill the database

You need to add AROs and ACOs by yourself in the corresponding tables. You can whether refer to an Object by giving its Model and its Foreign Key if the Object is stored in Database, or refer to it by giving the object an Alias.

Once you've set your AROs and ACOs you can use the plugin to link them together. Use the methods `allow(...)` and `deny(...)` to grant or deny ARO access to a ACO. You just need to do that once, it will be saved in database.

#### E.G.

We have the following AROs and ACOs tables :

| aros table |   |                |
|------------|---|----------------|
|   Group    | 1 | Administrators |
|   Group    | 2 | Users          |

| acos table |   |       |
|------------|---|-------|
|     -      | - | Forum |
|     -      | - | News  |

We can set the permissions in a controller doing :

```php 
    // ...
    
    $this->FlatAcl->allow(['model' => 'Group', 'id' => 2], 'News', ['read']);  // Allow Users to read the News only
    $this->FlatAcl->allow('Users', 'Forum', ['read', 'create']); // Allow users to read and create in the Forum
    
    $this->FlatAcl->allow('Administrators', 'News', '*'); // Give Administrators all permissions over the News
    $this->FlatAcl->allow('Administrators', 'Forum', '*'); // Give Administrators all permissions over the Forum
    $this->FlatAcl->deny('Administrators', 'Forum', 'delete'); // Deny Administrators the permissions to delete things on the Forum
```


### Check the permissions

Now that the database is full, we just have to check the permissions before we allow a User to do some actions.

```php 
    // ...
    
    public function beforeFilter() {
        parent::beforeFilter();
    
        // Check by giving all User's groups
        $group_ids = [1, 2, 5]; // You will probably look for them in session or somewhere
        $b = $this->FlatAcl->check(['model' => 'Group', 'id' => $group_ids], $this->name, $this->action);
        if(!$b) {
            return $this->redirect($this->referer());
        }
    
        // Or by simply giving group aliases
        $b = $this->FlatAcl->check(['Adinistrators', 'Forum Administrators'], $this->name, $this->action);
        if(!$b) {
            return $this->redirect($this->referer());
        }
    }
```

### And it's done !

I hope this plugin is useful to you.

In case of errors or bugs, do not hesitate to open an issue.

See you !
