<?php 


class Genv_Html {

	/**
	 * @var  array  preferred order of attributes
	 */
	public static $attribute_order = array
	(
		'action',
		'method',
		'type',
		'id',
		'name',
		'value',
		'href',
		'src',
		'width',
		'height',
		'cols',
		'rows',
		'size',
		'maxlength',
		'rel',
		'media',
		'accept-charset',
		'accept',
		'tabindex',
		'accesskey',
		'alt',
		'title',
		'class',
		'style',
		'selected',
		'checked',
		'readonly',
		'disabled',
	);

	/**
	 * @var  boolean  automatically target external URLs to a new window?
	 */
	public static $windowed_urls = FALSE;

	/**
	 * Convert special characters to Html entities. All untrusted content
	 * should be passed through this method to prevent XSS injections.
	 *
	 *     echo Genv_Html::chars($username);
	 *
	 * @param   string   string to convert
	 * @param   boolean  encode existing entities
	 * @return  string
	 */
	public static function chars($value, $double_encode = TRUE)
	{
		return htmlspecialchars( (string) $value, ENT_QUOTES, 'utf-8', $double_encode);
	}

	/**
	 * Convert all applicable characters to Html entities. All characters
	 * that cannot be represented in Html with the current character set
	 * will be converted to entities.
	 *
	 *     echo Genv_Html::entities($username);
	 *
	 * @param   string   string to convert
	 * @param   boolean  encode existing entities
	 * @return  string
	 */
	public static function entities($value, $double_encode = TRUE)
	{
		return htmlentities( (string) $value, ENT_QUOTES, 'utf8', $double_encode);
	}

	/**
	 * Create Html link anchors. Note that the title is not escaped, to allow
	 * Html elements within links (images, etc).
	 *
	 *     echo Genv_Html::anchor('/user/profile', 'My Profile');
	 *
	 * @param   string  URL or URI string
	 * @param   string  link text
	 * @param   array   Html anchor attributes
	 * @param   string  use a specific protocol
	 * @return  string
	 * @uses    URL::base
	 * @uses    URL::site
	 * @uses    Genv_Html::attributes
	 */
	public static function anchor($uri, $title = NULL, array $attributes = NULL, $protocol = NULL)
	{
		if ($title === NULL)
		{
			// Use the URI as the title
			$title = $uri;
		}

		if ($uri === '')
		{
			// Only use the base URL
			//$uri = URL::base(FALSE, $protocol);
		}
		else
		{
			if (strpos($uri, '://') !== FALSE)
			{
				if (Genv_Html::$windowed_urls === TRUE AND empty($attributes['target']))
				{
					// Make the link open in a new window
					$attributes['target'] = '_blank';
				}
			}
			elseif ($uri[0] !== '#')
			{
				// Make the URI absolute for non-id anchors
				$uri = U($uri, $protocol);
			}
		}

		// Add the sanitized link to the attributes
		$attributes['href'] = $uri;

		return '<a'.Genv_Html::attributes($attributes).'>'.$title.'</a>';
	}

	/**
	 * Creates an Html anchor to a file. Note that the title is not escaped,
	 * to allow Html elements within links (images, etc).
	 *
	 *     echo Genv_Html::file_anchor('media/doc/user_guide.pdf', 'User Guide');
	 *
	 * @param   string  name of file to link to
	 * @param   string  link text
	 * @param   array   Html anchor attributes
	 * @param   string  non-default protocol, eg: ftp
	 * @return  string
	 * @uses    URL::base
	 * @uses    Genv_Html::attributes
	 */
	public static function file_anchor($file, $title = NULL, array $attributes = NULL, $protocol = NULL)
	{
		if ($title === NULL)
		{
			// Use the file name as the title
			$title = basename($file);
		}

		// Add the file link to the attributes
		$attributes['href'] =U(FALSE, $protocol).$file;

		return '<a'.Genv_Html::attributes($attributes).'>'.$title.'</a>';
	}

	/**
	 * Generates an obfuscated version of a string. Text passed through this
	 * method is less likely to be read by web crawlers and robots, which can
	 * be helpful for spam prevention, but can prevent legitimate robots from
	 * reading your content.
	 *
	 *     echo Genv_Html::obfuscate($text);
	 *
	 * @param   string  string to obfuscate
	 * @return  string
	 * @since   3.0.3
	 */
	public static function obfuscate($string)
	{
		$safe = '';
		foreach (str_split($string) as $letter)
		{
			switch (rand(1, 3))
			{
				// Html entity code
				case 1:
					$safe .= '&#'.ord($letter).';';
				break;

				// Hex character code
				case 2:
					$safe .= '&#x'.dechex(ord($letter)).';';
				break;

				// Raw (no) encoding
				case 3:
					$safe .= $letter;
			}
		}

		return $safe;
	}

	/**
	 * Generates an obfuscated version of an email address. Helps prevent spam
	 * robots from finding email addresses.
	 *
	 *     echo Genv_Html::email($address);
	 *
	 * @param   string  email address
	 * @return  string
	 * @uses    Genv_Html::obfuscate
	 */
	public static function email($email)
	{
		// Make sure the at sign is always obfuscated
		return str_replace('@', '&#64;', Genv_Html::obfuscate($email));
	}

	/**
	 * Creates an email (mailto:) anchor. Note that the title is not escaped,
	 * to allow Html elements within links (images, etc).
	 *
	 *     echo Genv_Html::mailto($address);
	 *
	 * @param   string  email address to send to
	 * @param   string  link text
	 * @param   array   Html anchor attributes
	 * @return  string
	 * @uses    Genv_Html::email
	 * @uses    Genv_Html::attributes
	 */
	public static function mailto($email, $title = NULL, array $attributes = NULL)
	{
		// Obfuscate email address
		$email = Genv_Html::email($email);

		if ($title === NULL)
		{
			// Use the email address as the title
			$title = $email;
		}

		return '<a href="&#109;&#097;&#105;&#108;&#116;&#111;&#058;'.$email.'"'.Genv_Html::attributes($attributes).'>'.$title.'</a>';
	}

	/**
	 * Creates a style sheet link element.
	 *
	 *     echo Genv_Html::style('media/css/screen.css');
	 *
	 * @param   string  file name
	 * @param   array   default attributes
	 * @param   boolean  include the index page
	 * @return  string
	 * @uses    URL::base
	 * @uses    Genv_Html::attributes
	 */
	public static function style($file, array $attributes = NULL, $index = FALSE)
	{
		if (strpos($file, '://') === FALSE)
		{
			// Add the base URL
			$file = U($index).$file;
		}

		// Set the stylesheet link
		$attributes['href'] = $file;

		// Set the stylesheet rel
		$attributes['rel'] = 'stylesheet';

		// Set the stylesheet type
		$attributes['type'] = 'text/css';

		return '<link'.Genv_Html::attributes($attributes).' />';
	}

	/**
	 * Creates a script link.
	 *
	 *     echo Genv_Html::script('media/js/jquery.min.js');
	 *
	 * @param   string   file name
	 * @param   array    default attributes
	 * @param   boolean  include the index page
	 * @return  string
	 * @uses    URL::base
	 * @uses    Genv_Html::attributes
	 */
	public static function script($file, array $attributes = NULL, $index = FALSE)
	{
		if (strpos($file, '://') === FALSE)
		{
			// Add the base URL
			$file = U($index).$file;
		}

		// Set the script link
		$attributes['src'] = $file;

		// Set the script type
		$attributes['type'] = 'text/javascript';

		return '<script'.Genv_Html::attributes($attributes).'></script>';
	}

	/**
	 * Creates a image link.
	 *
	 *     echo Genv_Html::image('media/img/logo.png', array('alt' => 'My Company'));
	 *
	 * @param   string   file name
	 * @param   array    default attributes
	 * @return  string
	 * @uses    URL::base
	 * @uses    Genv_Html::attributes
	 */
	public static function image($file, array $attributes = NULL, $index = FALSE)
	{
		if (strpos($file, '://') === FALSE)
		{
			// Add the base URL
			$file = URL::base($index).$file;
		}

		// Add the image link
		$attributes['src'] = $file;

		return '<img'.Genv_Html::attributes($attributes).' />';
	}

	/**
	 * Compiles an array of Html attributes into an attribute string.
	 * Attributes will be sorted using Genv_Html::$attribute_order for consistency.
	 *
	 *     echo '<div'.Genv_Html::attributes($attrs).'>'.$content.'</div>';
	 *
	 * @param   array   attribute list
	 * @return  string
	 */
	public static function attributes(array $attributes = NULL)
	{
		if (empty($attributes))
			return '';

		$sorted = array();
		foreach (Genv_Html::$attribute_order as $key)
		{
			if (isset($attributes[$key]))
			{
				// Add the attribute to the sorted list
				$sorted[$key] = $attributes[$key];
			}
		}

		// Combine the sorted attributes
		$attributes = $sorted + $attributes;

		$compiled = '';
		foreach ($attributes as $key => $value)
		{
			if ($value === NULL)
			{
				// Skip attributes that have NULL values
				continue;
			}

			if (is_int($key))
			{
				// Assume non-associative keys are mirrored attributes
				$key = $value;
			}

			// Add the attribute value
			$compiled .= ' '.$key.'="'.Genv_Html::chars($value).'"';
		}

		return $compiled;
	}

} // End html
