<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Blog\Models;


use Bootgly\ADI\Databases\SQL\Model\Column;
use Bootgly\ADI\Databases\SQL\Model\Key;
use Bootgly\ADI\Databases\SQL\Model\Table;


#[Table('posts')]
class Post
{
   // * Data
   #[Key]
   public null|int $id = null;

   #[Column]
   public string $title = '';

   #[Column]
   public string $body = '';

   #[Column('created_at')]
   public null|string $created = null;
}
