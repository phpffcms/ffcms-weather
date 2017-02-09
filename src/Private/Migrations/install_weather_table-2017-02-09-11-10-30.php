<?php

use Ffcms\Core\Helper\Serialize;
use Ffcms\Core\Migrations\MigrationInterface;
use Ffcms\Core\Migrations\Migration;

/**
 * Class install_weather_table.
 */
class install_weather_table extends Migration implements MigrationInterface
{
    /**
     * Execute actions when migration is up
     * @return void
     */
    public function up()
    {
        $this->getSchema()->create('weathers', function ($table){
            $table->increments('id');
            $table->text('name');
            $table->string('latin_name');
            $table->string('country')->nullable();
            $table->integer('zip_code')->default(0);
            $table->binary('data')->nullable();
            $table->timestamps();
        });
        parent::up();
    }

    /**
     * Seed created table via up() method with some data
     * @return void
     */
    public function seed()
    {
        $this->getConnection()->table('weathers')->insert([
            [
                'name' => Serialize::encode(['en' => 'Moscow', 'ru' => 'Москва']),
                'latin_name' => 'moscow',
                'country' => 'ru',
                'created_at' => $this->now,
                'updated_at' => $this->now
            ]
        ]);
    }

    /**
     * Execute actions when migration is down
     * @return void
     */
    public function down()
    {
        $this->getSchema()->dropIfExists('weathers');
        parent::down();
    }
}