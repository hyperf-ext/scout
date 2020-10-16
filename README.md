# Hyperf 模型全文搜索组件

## 简介

[`hyperf-ext/scout`](https://github.com/hyperf-ext/scout) 组件为 Hyperf 模型的全文搜索提供了基于 Elasticsearch 的简单的解决方案。通过使用模型观察者，组件会自动同步模型记录的搜索索引。

该组件移植自 [`Laravel Scout`](https://github.com/laravel/scout )，与 Laravel Scout 不同的是，为了提升使用体验并更好的与 Elasticsearch 结合，该组件移除了自定义驱动的特性，仅支持 Elasticsearch，同时针对 Elasticsearch 的特性在原来的基础上进行了诸多的优化，通过队列更新索引的方式也改为通过协程实现。

另外，组件在搜索查询构造器中通过 [`ongr/elasticsearch-dsl`](https://github.com/ongr-io/ElasticsearchDSL) 包来使用 Elasticsearch DSL 构建复杂的查询条件。

> 注意，组件依赖的 `elasticsearch/elasticsearch` 包版本为 `^7.9`，映射类型已被废弃并将在 `8.0` 中彻底移除，因此组件同样也不提供映射类型的支持，即一个模型对应一个索引。
> 
> 使用独立的索引取代使用映射类型可以让数据更倾向于密集而非稀疏，并且由于同一个索引中的所有的文档表示为同一种实体，在通过全文搜索时打分的条件统计会更为精确。
 

## 安装

```shell script
composer require hyperf-ext/scout
```

> 该组件自动依赖了 [`hyperf-ext/elasticsearch`](https://github.com/hyperf-ext/elasticsearch) 组件来使用 Elasticsearch 客户端，请在本组件安装完成后发布此组件的配置。

Scout 安装完成后，使用 `vendor:publish` 命令来生成 Scout 配置文件。这个命令将在你的 `config/autoload` 目录下生成一个 `scout.php` 配置文件。

```shell script
php bin/hyperf.php vendor:publish hyperf-ext/scout
```

最后，在你要做搜索的模型中添加 `HyperfExt\Scout\Searchable` Trait。这个 Trait 会自动注册一个模型观察者来保持模型和 Elasticsearch 的同步：

```php
use Hyperf\Database\Model\Model;
use HyperfExt\Scout\Searchable;

class Post extends Model
{
    use Searchable;
}
```

### 默认配置

```php
[
    // 索引前缀
    'prefix' => env('SCOUT_PREFIX', ''),
    // 命令行操作时的协程并发数
    'concurrency' => env('SCOUT_CONCURRENCY', 100),
    // 控制批量操作文档时分块处理的数量
    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],
    // 是否启用索引文档软删除
    'soft_delete' => true,
    // 控制索引、更新、删除等文档操作所做的更改何时对搜索可见，可能的值 true（或空字符串）、false、'wait_for'
    // 详细描述 https://www.elastic.co/guide/en/elasticsearch/reference/current/docs-refresh.html
    'document_refresh' => true,
];
```

## 配置模型

### 配置 Elasticsearch 索引设置

索引设置可以通过在模型中添加 `scoutSettings` 属性或重写 `getScoutSettings` 方法来配置：

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Hyperf\Database\Model\Model;
use HyperfExt\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    // 使用 `scoutSettings` 属性配置
    protected $scoutSettings = [
        'analysis' => [
            'analyzer' => [
                'es_std' => [
                    'type' => 'standard',
                    'stopwords' => '_spanish_'
                ]
            ]    
        ],
    ];

    // 或者重写 `getScoutSettings` 方法配置
    public function getScoutSettings(): ?array
    {
        return [
            'analysis' => [
                'analyzer' => [
                    'es_std' => [
                        'type' => 'standard',
                        'stopwords' => '_spanish_'
                    ]
                ]    
            ],
        ];
    }
}
```

### 配置 Elasticsearch 映射

映射可以通过在模型中添加 `scoutMapping` 属性或重写 `getScoutMapping` 方法来配置：

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Hyperf\Database\Model\Model;
use HyperfExt\Scout\Searchable;

class Post extends Model
{
    use Searchable;
    
    // 使用 `scoutMapping` 属性配置
    protected $scoutMapping = [
       'properties' => [
           'title' => ['type' => 'text'],
       ],
    ];

    // 或者重写 `getScoutMapping` 方法配置
    public function getScoutMapping(): array
    {
        return [
            'properties' => [
                'title' => ['type' => 'text'],
            ],
        ];
    }
}
```

### 配置 Elasticsearch 索引名称

每个模型都是通过给定的索引名称创建「索引」来与 Elasticsearch 进行同步，该「索引」下包含所有可搜索的模型记录。换句话说，你可以把每一个「索引」设想为一张 MySQL 数据表。

默认情况下，每个模型都会被持久化到与模型的「表」名（通常是模型名称的复数形式）相匹配的索引，如果在配置文件中设置了索引前缀，则会在表名前拼接该前缀。你可以通过重写模型上的 `searchableAs` 方法来自定义索引名称：

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Hyperf\Database\Model\Model;
use HyperfExt\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    /**
     * 获取模型的索引名称。
     */
    public function searchableAs(): string
    {
        return 'posts_index';
    }
}
```

### 配置可搜索数据

默认情况下，通过模型的 `toArray` 方法将返回的数据持久化到搜索索引。如果要自定义同步到搜索索引的数据，可以覆盖模型上的 `toSearchableArray` 方法：

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Hyperf\Database\Model\Model;
use HyperfExt\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    /**
     * 获取模型的可搜索数据。
     */
    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        // 自定义数组...

        return $array;
    }
}
```

### 配置模型 ID

默认情况下，Scout 将使用模型的主键作为搜索索引中存储的唯一 ID。可以通过模型上的 `getScoutKey` 和 `getScoutKeyName` 方法自定义：

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Hyperf\Database\Model\Model;
use HyperfExt\Scout\Searchable;

class User extends Model
{
    use Searchable;

    /**
     * 获取模型主键。
     *
     * @return mixed
     */
    public function getScoutKey()
    {
        return $this->email;
    }

    /**
     * 获取模型键名。
     *
     * @return mixed
     */
    public function getScoutKeyName()
    {
        return 'email';
    }
}
```

## 索引

### 创建索引并更新映射

```shell script
php bin/hyperf.php scout:index:create "App\Models\Post"
```

### 更新索引设置

```shell script
php bin/hyperf.php scout:index:update "App\Models\Post"
```

### 删除索引

```shell script
php bin/hyperf.php scout:index:drop "App\Models\Post"
```

### 更新映射

```shell script
php bin/hyperf.php scout:mapping:update "App\Models\Post"
```

### 批量导入

如果你想安装 Scout 到已存在的项目中，你可能已经有了想要导入搜索驱动的数据库记录。Scout 提供了 `scout:import` 命令来导入所有已存在的记录到搜索索引：

```shell script
php bin/hyperf.php scout:import "App\Models\Post"
```

`sscout:flush` 命令可用于从搜索索引中删除所有模型的记录：

```shell script
php bin/hyperf.php scout:flush "App\Models\Post"
```

### 添加记录

当你将 `HyperfExt\Scout\Searchable` trait 添加到模型中，你需要做的就是 `save` 一个模型实例。更新索引操作将会在协程结束时进行，不会堵塞请求。

```php
$order = new App\Models\Order;

// ...

$order->save();
```

### 通过查询添加

如果你想通过查询构造器将模型集合添加到搜索索引中，你可以在模型查询构造器上链式调用 `searchable` 方法。`searchable` 会把构造器的查询结果*分块*并且将记录添加到你的搜索索引里。同样的，所有的数据块将在协程中添加。

```php
// 通过模型查询构造器添加...
App\Models\Order::where('price', '>', 100)->searchable();

// 你也可以通过模型关系增加记录...
$user->orders()->searchable();

// 你也可以通过集合增加记录...
$orders->searchable();
```

`searchable` 方法可以被看做是「更新插入」的操作。换句话说，如果模型记录已经在你的索引里了，它就会被更新。如果搜索索引中不存在，则将其添加到索引中。

### 更新记录

要更新可搜索的模型，只需要更新模型实例的属性并将模型 `save` 到数据库。Scout 会自动将更新同步到你的搜索索引中：

```php
$order = App\Models\Order::find(1);

// 更新订单...

$order->save();
```

你也可以在模型查询语句上使用 `searchable` 方法来更新一个模型的集合。如果这个模型不存在你检索的索引里，就会被创建：

```php
//  通过模型查询更新...
App\Models\Order::where('price', '>', 100)->searchable();

// 你也可以通过数据间的关联进行更新...
$user->orders()->searchable();

// 你也可以通过数据集合进行更新...
$orders->searchable();
```

### 删除记录

使用 `delete` 从数据库中删除该模型就可以移除索引里的记录。这种删除形式甚至与*软删除*的模型兼容:

```php
$order = App\Models\Order::find(1);

$order->delete();
```

如果你不希望记录在删除之前被检索到，可以在模型查询实例或集合上使用 `unsearchable` 方法：

```php
// 通过模型查询删除...
App\Models\Order::where('price', '>', 100)->unsearchable();

// 你可以通过数据间的关系进行删除...
$user->orders()->unsearchable();

// 你可以通过数据集合进行删除...
$orders->unsearchable();
```

### 暂停索引

你可能需要在批量执行模型操作的时候，不同步模型数据到搜索索引。此时你可以使用 `withoutSyncingToSearch` 方法来执行此操作。这个方法接受一个立即执行的回调。该回调中所有的操作都不会同步到模型的索引：

```php
App\Models\Order::withoutSyncingToSearch(function () {
    // 执行模型操作...
});
```

### 有条件的搜索模型实例

有时候你可能需要在某些条件下模型是可搜索的。例如，假设你有 `App\Models\Post` 模型可能两种状态之一：「草稿」和「发布」。你可能只允许搜索 「发布」过的帖子。为了实现这一点，你需要在模型中定义一个 `shouldBeSearchable` 方法：

```php
public function shouldBeSearchable()
{
    return $this->isPublished();
}
```

只有在通过 `save` 方法、查询或关联模型操作时，才应使用 `shouldBeSearchable` 方法。直接使用 `searchable` 方法将使模型或集合的可搜索结果覆盖 `shouldBeSearchable` 方法的结果:

```php
// 此处将遵循 "shouldBeSearchable" 结果...
App\Models\Order::where('price', '>', 100)->searchable();

$user->orders()->searchable();

$order->save();

// 此处将覆盖 "shouldBeSearchable" 结果...
$orders->searchable();

$order->searchable();
```

## 搜索

你可以使用 `search` 方法来搜索模型。`search` 方法接受一个用于搜索模型的字符串。你还需要在搜索查询上链式调用 `get` 方法，才能用给定的搜索语句查询与之匹配的模型。

> 传递给 `search` 方法的字符串参数即 [query_string](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html) 的 `query` 参数。

```php
// 获取模型集合
$orders = App\Models\Order::search('Star Trek')->get();

// 获取单个模型
$order = App\Models\Order::search('Star Trek')->first();
```

如果你想在它们返回模型前得到原结果，你应该使用 `getRaw` 方法：

```php
$orders = App\Models\Order::search('Star Trek')->getRaw();
```

搜索查询通常会在模型的 `searchableAs` 方法指定的索引上执行。当然，你也可以使用 `within` 方法指定应该搜索的自定义索引：

```php
$orders = App\Models\Order::search('Star Trek')
    ->within('tv_shows_popularity_desc')
    ->get();
```

### 查询构造器

通过 `search` 方法返回的查询构造器，你可以使用其提供的一系列方法构造复杂的查询条件。

```php
use HyperfExt\Scout\QueryBuilder;

$films = App\Models\Film::search()
    ->mustWhereLike('title', 'Star*')
    ->shouldWhere('category_id', 1)
    ->shouldWhere('category_id', 5)
    ->whereBetween('rating', [7, 10])
    ->mustWhereNested('tags', function (QueryBuilder $query) {
        $query->whereMatch('tags.title', 'sf');
    })
    ->notWhere('id', 2)
    ->minScore(0.5)
    ->size(5)
    ->orderBy('created_at', 'desc')
    ->get();
```

#### 可用的 Where 方法

以下列出的以 `where` 开头的方法同时提供以 `mustWhere`、`shouldWhere`、`notWhere` 开头的版本。

`where` 开头的方法使用 `filter` Occurrence 进行查询，`mustWhere` 方法使用 `must`，`shouldWhere` 方法使用 `should`，`notWhere` 方法使用 `must_not`。

```php
whereIn(string $field, array $values, array $parameters = [])
whereBetween(string $field, array $values, array $parameters = [])
whereExists(string $field)
whereRegexp(string $field, string $value, string $flags = 'ALL')
whereLike(string $field, string $value, array $parameters = [])
wherePrefix(string $field, string $value, array $parameters = [])
whereFuzzy(string $field, string $value, array $parameters = [])
whereIdsIn(array $values, array $parameters = [])
whereGeoDistance(string $field, string $distance, $location, array $parameters = [])
whereGeoBoundingBox(string $field, array $values, array $parameters = [])
whereGeoPolygon(string $field, array $points, array $parameters = [])
whereGeoShape(Closure $closure, array $parameters = ['relation' => 'INTERSECTS'])
whereMatchAll(array $parameters = [])
whereMatch(string $field, string $value, array $parameters = [])
whereMultiMatch(array $fields, string $value, array $parameters = [])
whereMatchPhrase(string $field, string $value, array $parameters = [])
whereMatchPhrasePrefix(string $field, string $value, array $parameters = [])
whereQueryString(string $value, array $parameters = [])
whereSimpleQueryString(string $value, array $parameters = [])
whereNested(string $path, Closure $callback, array $parameters = [])
```

#### 使用 DSL 查询

组件的 Elasticsearch DSL 支持由 [`ongr/elasticsearch-dsl`](https://github.com/ongr-io/ElasticsearchDSL) 包提供，使用方法请访问项目主页查阅。

```php
use ONGR\ElasticsearchDSL\Search;

// 通过 `dsl` 方法传递闭包函数来访问 `Search` 实例
$orders = App\Models\Order::search()->dsl(function (Search $search) {
    // ...
})->get();

// 或者通过 `getSearch` 方法获取 `Search` 实例
$query = App\Models\Order::search();
$search = $query->getSearch();
$search->addQuery(...);
$orders = $query->get();
```

#### 使用原始数组查询

```php
$orders = App\Models\Order::search()->raw([
    'bool' => [
        'must' => [
            'match' => [...],
            'term' => [...],
        ],
    ],
])->get();
```

### 分页

除了检索模型的集合，您也可以使用 `paginate` 方法对搜索结果进行分页。这个方法会返回一个就像传统的模型查询分页一样的 `Paginator` 实例：

```php
$orders = App\Models\Order::search('Star Trek')->paginate();
```

您可以通过将数量作为第一个参数传递给 `paginate` 方法来指定每页检索多少个模型：

```php
$orders = App\Models\Order::search('Star Trek')->paginate(15);
```

### 软删除

如果您索引的模型配置了[软删除](https://hyperf.wiki/2.0/#/zh-cn/db/model?id=%e8%bd%af%e5%88%a0%e9%99%a4 )，并且您需要搜索已删除的模型，请设置 `config/autoload/scout.php` 中的 `soft_delete` 选项为 `true`（默认值）：

```php
'soft_delete' => true,
```

当此配置选项为 `true` 时，Scout 将不会从搜索索引中删除软删除的模型。并且在查询时，其逻辑与传统的模型是一致的，因此请确保索引的数据中包含软删除的标识字段，通常是 `deleted_at`。然后，您可以在搜索时使用 `withTrashed` 或 `onlyTrashed` 方法来检索软删除的记录：

```php
// 检索结果时包含已删除记录...
$orders = App\Models\Order::search('Star Trek')->withTrashed()->get();

// 检索结果时仅包含已删除记录...
$orders = App\Models\Order::search('Star Trek')->onlyTrashed()->get();
```

> 当使用 `forceDelete` 软删除的模型被永久删除时，Scout 会自动将其从搜索索引中删除。
