<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          'BtU2iAmVSV2SrVcY);75k0>n9wFVx~)d^`LGU!%+{PmGX<K,esl&K/iYs75n]*Tg' );
define( 'SECURE_AUTH_KEY',   'i4bKHYxC1~M0-/L/SfT2WT|J{;a.+Gy{HbzIzq{wItA;p04YJ#A0b)GyhGQeILgo' );
define( 'LOGGED_IN_KEY',     'T{8oMxnJw}R5tM<hr4sn&_PlLOaIh/o8@Jow1|yJEyorAwvu1W?<((7+Rp,Ou2/W' );
define( 'NONCE_KEY',         '5F|EA0Id?K%Ia~^YYXse[:7rsFNL5*s=Z=J7Lm=aI*df(^ALuK2SN$FJJxnT$Ymg' );
define( 'AUTH_SALT',         ')aWJ*urbgn4)0`;u>[k-C/#,;])|ZRyOFx^qb:?xE|Q(I#3}^LcrRvV(dG-tjj!>' );
define( 'SECURE_AUTH_SALT',  '^xqh?if2vyCq)O3E;TzaYvTlu4zB>5),OHShL(qtN&w-6w@^#]$U:fzu7ay #QM#' );
define( 'LOGGED_IN_SALT',    'x]SKe(o{ N82ntUzroP$}*k8aHn8mgk[W@<3-PA6t:I9}NN-Tu;V[5qmtrX^[Txn' );
define( 'NONCE_SALT',        ':n:D*T6mBy><+)W*nbwR$^nm8q2=t)HNza(*s)N:RiGp2KIB(O&0@kk&FIn%]9QF' );
define( 'WP_CACHE_KEY_SALT', '8P:L8&4 ;*:{f/vKLgyPj*8viN,)CV420ay_ #ZD}>h!i#?>#0j(!xFn[+%CpXh#' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
