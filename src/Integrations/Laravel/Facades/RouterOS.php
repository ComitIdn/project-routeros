<?php

namespace RouterOSFramework\Integrations\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class RouterOS
 * Laravel Facade for Google/Project RouterOS Framework.
 *
 * @method static \RouterOSFramework\Modules\Hotspot hotspot()
 * @method static \RouterOSFramework\Modules\Voucher voucher()
 * @method static \RouterOSFramework\Modules\PPPoE pppoe()
 * @method static \RouterOSFramework\Modules\Ptmp ptmp()
 * @method static \RouterOSFramework\Modules\Firewall firewall()
 * @method static \RouterOSFramework\Modules\Queue queue()
 * @method static \RouterOSFramework\Modules\NetworkInterface interface()
 * @method static \RouterOSFramework\Modules\System system()
 * @method static \RouterOSFramework\Modules\Ip ip()
 * @method static \RouterOSFramework\Actions\Creator create()
 * @method static \RouterOSFramework\Actions\Writer write()
 * @method static \RouterOSFramework\Actions\Reader read()
 * @method static \RouterOSFramework\Actions\Finder find()
 * @method static \RouterOSFramework\Actions\Setter set()
 * @method static \RouterOSFramework\Actions\Getter get()
 * @method static \RouterOSFramework\Actions\Printer print()
 * @method static \RouterOSFramework\Actions\Remover remove()
 * @method static \RouterOSFramework\Actions\Toggler toggle()
 * @method static bool setProtocol(string $protocol, ?int $port = null)
 * @method static string getProtocol()
 * @method static \RouterOSFramework\Core\RouterOS useRest(?int $port = null)
 * @method static \RouterOSFramework\Core\RouterOS useSsh(?int $port = null)
 * @method static \RouterOSFramework\Core\RouterOS useTelnet(?int $port = null)
 * @method static \RouterOSFramework\Core\RouterOS useApi(?int $port = null)
 * @method static array setLimit(string $username, ?string $limitUptime = null, $limitBytes = null, string $path = '/ip/hotspot/user')
 * @method static array setComment(string $username, string $comment, string $path = '/ip/hotspot/user')
 * @method static array setEmail(string $username, string $email, string $path = '/ip/hotspot/user')
 * @method static array setName(string $idOrOldName, string $newName, string $path = '/ip/hotspot/user')
 * @method static ?string findNameToId(string $name, string $path = '/ip/hotspot/user')
 *
 * @see \RouterOSFramework\Core\RouterOS
 */
class RouterOS extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'routeros';
    }
}
