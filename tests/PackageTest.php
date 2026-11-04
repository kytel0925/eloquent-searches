<?php

namespace Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use SlothDevGuy\Searches\EloquentSearchServiceProvider;
use SlothDevGuy\Searches\Search;
use Tests\Fixtures\Models\Note;
use Tests\Fixtures\Models\Person;

class PackageTest extends TestCase
{
    public function test_the_configuration_is_merged(): void
    {
        $this->assertSame(100, config('searches.request.max_per_page'));
    }

    public function test_the_configuration_can_be_published(): void
    {
        $paths = ServiceProvider::pathsToPublish(EloquentSearchServiceProvider::class, 'searches-config');

        $this->assertSame([config_path('searches.php')], array_values($paths));
        $this->assertFileExists(array_key_first($paths));
    }

    public function test_the_model_trait(): void
    {
        Note::query()->create(['title' => 'alpha']);
        Note::query()->create(['title' => 'beta']);

        $this->assertSame(['beta'], Note::search(['title' => 'beta'])->get()->pluck('title')->all());
        $this->assertSame(2, (new Note)->newSearch()->count());
        $this->assertNull((new Note)->searchableFields());
    }

    public function test_the_helpers(): void
    {
        $this->assertInstanceOf(Search::class, eloquent_search(new Note, ['title' => 'alpha']));
        $this->assertFalse(function_exists('warning'));

        $this->assertNull(morph_model_alias(Person::class));

        config(['searches.morphs.build_default_aliases' => true]);
        $this->assertSame('person', morph_model_alias(new Person));

        Relation::morphMap(['human' => Person::class]);
        $this->assertSame('human', morph_model_alias(Person::class));
        Relation::$morphMap = [];
    }
}
