<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Demo\Auth\Models;


use Bootgly\ADI\Databases\SQL\Model\Column;
use Bootgly\ADI\Databases\SQL\Model\Key;
use Bootgly\ADI\Databases\SQL\Model\Table;


#[Table('users')]
class User
{
   // * Data
   #[Key]
   public null|int $id = null;

   #[Column]
   public string $email = '';

   #[Column]
   public string $password = '';

   #[Column('email_verified_at')]
   public null|int $verified = null;

   #[Column('created_at')]
   public null|string $created = null;
}
