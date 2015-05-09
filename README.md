# basecamp-json-api
a basecamp JSON API wrapper in PHP

##Usage

```php
// construct a new object to access your account data
// config => username, password, accountId, contact info, etc
$bc = new Basecamp($config);

// fetch a project object
$project = $bc->Project->read(1234);

// fetch todo lists for that project
$todolists = $project->TodoList->list();

// fetch a single todo list to use / query
$todoList = $project->TodoList->read($todolists[0]['id']);

// fetch todos for from that list
$todos = $todoList->Todo->list();

// fetch / instantiate a todo object from one of the todos in that list
$todo = $todoList->Todo->read($todos[0]['id']);

// use that todo object to add a comment
$todo->Comment->add(array('content' => 'You must complete this'));
```
