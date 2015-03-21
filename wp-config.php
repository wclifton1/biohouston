<?php
/**
 * The base configurations of the WordPress.
 *
 * This file has the following configurations: MySQL settings, Table Prefix,
 * Secret Keys, and ABSPATH. You can find more information by visiting
 * {@link http://codex.wordpress.org/Editing_wp-config.php Editing wp-config.php}
 * Codex page. You can get the MySQL settings from your web host.
 *
 * This file is used by the wp-config.php creation script during the
 * installation. You don't have to use the web site, you can just copy this file
 * to "wp-config.php" and fill in the values.
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define('DB_NAME', 'enventur_biohouston');

/** MySQL database username */
define('DB_USER', 'enventur_website');

/** MySQL database password */
define('DB_PASSWORD', 'Biohouston2015');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/** Database Charset to use in creating database tables. */
define('DB_CHARSET', 'utf8');

/** The Database Collate type. Don't change this if in doubt. */
define('DB_COLLATE', '');

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         ':Q9YX&~x@t7pqE!F/[Eo4DraNSZd//~e3eUE-(i>$:D`=uF`Ru1%GX{-+C@%7B#{');
define('SECURE_AUTH_KEY',  'v-J|vdnm: :-MPFfNV?90|1RTEqT:wf<)(~>e3 bN4 /]%G[b!-+F[e)1Uj`yDp:');
define('LOGGED_IN_KEY',    '@at]3O8su*jjsKn&c4HG+<a++x#{3iMQ3:OMC>AnxKuf@c4a5&d9iW0k~GB!.ror');
define('NONCE_KEY',        'Ih|]eoB;^`=HCkbYsTK1/>UWcthxy65vNWF((-E}q3$iaEdNi^W?-njsMeO0T8&o');
define('AUTH_SALT',        'n:K<<DzW~}~K<HC^e6oF]l!}>B<N0-|GA`&X.C4y<sc$#i{{-|N?cA`eZ;b/T3rJ');
define('SECURE_AUTH_SALT', 'xLK~UurO)6 3/+J1-_-ir(A.iV){V8_&/7=o$J 3VidA-co,Z#X$dD_#--MPvMKT');
define('LOGGED_IN_SALT',   '>3CWj{TFM0`Pu~>[r)s9ZOfn!.Tbzg7-Wm:YXhr8hlgI{`tQk|ki^FwAJAJ(+|I8');
define('NONCE_SALT',       '1-8lV$=S)X`KN Ah]g#rul-%yoGN^<Zp%gXW8#@!~O.1{-d]r3+.-+F^&6)zkW#H');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each a unique
 * prefix. Only numbers, letters, and underscores please!
 */
$table_prefix  = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 */
define('WP_DEBUG', false);

/* That's all, stop editing! Happy blogging. */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
