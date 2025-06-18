<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://wordpress.org/documentation/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'rssautowppublisher' );

/** Database username */
define( 'DB_USER', 'rssawpp_user1' );

/** Database password */
define( 'DB_PASSWORD', 'papanoel' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'mqJD4=~(/;Vhh_K`bE{tQ|;qe61}cK((JQsjp(Glk9A;c=G(yFTDBxBBxg+T^qTP' );
define( 'SECURE_AUTH_KEY',  '0##X<v9k8EA9<t,j^)P/s+S4*(E5out7YSTe0U6}BQea6[9$f=,]KPcGUR:J<dTP' );
define( 'LOGGED_IN_KEY',    'h_Pl(d@AJSjR6JAdWB2hrLwNWh<$nXjE84XiXc762J[Qyvq1_4E N@YU*KgmCh3:' );
define( 'NONCE_KEY',        'cUf=)@{VUMjkraf]WuG%+.ok0EM4/0C_wQ/6hmy-<f_-tAz)NS+_$o&O(vLN!PwS' );
define( 'AUTH_SALT',        'nl>An$[h8cBD*Hwnx{.E#h[#kQ3UhM{?/vf|_)EL.c5oPGqcEmOf 26%{-|L?C]/' );
define( 'SECURE_AUTH_SALT', 'CLlB $nX_fQ<dO)QQafmzM@*Y1!Tx!H}QdHOS>otq[+JCCO/ss`]_?NVZ]2Zc;2?' );
define( 'LOGGED_IN_SALT',   '9$#80[N,3TxQUr^p2EhJ!HP6a=nWVp0w(ETA#>6CSed[Lpef}U@c8YtvdZwR~A&[' );
define( 'NONCE_SALT',       'sX{Bt`GA+XhgRJ}MjLP^d[XKSHSNs{j!W<[Z0e]pvqls!rb,$0}D#-7LO$*<)4Tc' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
 * @link https://wordpress.org/documentation/article/debugging-in-wordpress/
 */
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
@ini_set('display_errors', 0);

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
