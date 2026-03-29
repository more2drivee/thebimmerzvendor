<?php

namespace App\Http;

use Illuminate\Support\Facades\Lang;
use Nwidart\Menus\Presenters\Presenter;

class AdminlteCustomPresenter extends Presenter
{
    static $id;
    private $titles = [];

    /**
     * {@inheritdoc}.
     */


    public function process($id)
    {
        self::$id = $id;
    }

    public function getOpenTagWrapper()
    {

        if(self::$id == "Dashboards"){
            return '<div class="tw-flex-1 tw-p-3 tw-space-y-3 tw-overflow-y-auto  tw-border-gray-200" style="display: flex ;" id="side-bar">' . PHP_EOL;
        }else{
            return '<div class="tw-flex-1 tw-p-3 tw-space-y-3 tw-overflow-y-auto tw-border-r tw-border-gray-200" style="display: flex; background-color: white ; border-radius: 10px 10px 10px 10px; " id="side-bar">' . PHP_EOL;
        }
    }

    /**
     * {@inheritdoc}.
     */
    public function getCloseTagWrapper()
    {


        return '</div>' . PHP_EOL;
    }

    /**
     * {@inheritdoc}.
     */
    public function getMenuWithoutDropdownWrapper($item)
    {


//        $keywords = [
//            'Accounting',
//            'Administer Backup',
//            'Modules',
//            'Notification Templates',
//            'CRM',
//        ];

//        foreach ($keywords as $keyword) {
//            $translation = Lang::get('messages.' . $keyword, [], 'lang_v1');
//            if ($translation == $keyword) {
////                info("Keyword '{$keyword}' not found in lang/en.");
//            }
//        }
        info($item->title);

        if(self::$id == "Payment Accounts" || self::$id == "الحسابات"){

            if ($item->title == "Home" || $item->title == "الرئيسية" ) {
                // Check for specific title to render different SVGs
                $svg = '';
                if ($item->title == "Home") {
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" style="width:15px; height:15px; position:absolute;" class="hover-image"><path style="width: 15px;" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>';
                } elseif ($item->title == "الرئيسية") {
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" style="width:15px; height:15px; position:absolute;" class="hover-image"><path style="width: 15px;" d="M310.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L242.7 256 73.4 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>';
                }
                return '<a href="' . $item->getUrl() . '" title="" class="tw-flex tw-items-center tw-gap-3 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-tracking-tight tw-text-gray-600 tw-transition-all tw-duration-200 tw-rounded-lg tw-whitespace-nowrap hover:tw-text-gray-900 hover:tw-bg-gray-100' . $this->getActiveState($item) . '" ' . $item->getAttributes() . '>' .
                    '<div style="position:relative; display:flex; align-items:center; justify-content:center; width:35px; height:35px;">' .
                    // Default Image (Image 1)
                    '<img src="' . asset('/uploads/images/8.png') . '" class="default-image" style="width:35px; height:35px; object-fit:cover;" />' .
                    // Display SVG based on the title condition
                    $svg .
                    '</div>' .
                    ' <span class="tw-truncate">' . self::$id . '</span>' .
                    '</a>' . PHP_EOL;
            } elseif ($item->title == "Accounting" || $item->title == "الحسابات") {
                return '<a href="' . $item->getUrl() . '" title="" class="tw-flex tw-items-center tw-gap-3 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-tracking-tight tw-text-gray-600 tw-transition-all tw-duration-200 tw-rounded-lg tw-whitespace-nowrap hover:tw-text-gray-900 hover:tw-bg-gray-100' . $this->getActiveState($item) . '" ' . $item->getAttributes() . '>' .
                    ' <span class="tw-truncate" style="padding-left: 15px;padding-bottom: 14px">' . $item->title . '</span>' .
                    '</a>' . PHP_EOL;
            }

        }elseif (self::$id == "Dashboards"){

        }
        else {
            if ($item->title == "Home" || $item->title == "الرئيسية" ) {
                // Check for specific title to render different SVGs
                $svg = '';
                if ($item->title == "Home") {
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" style="width:15px; height:15px; position:absolute;" class="hover-image"><path style="width: 15px;" d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l192 192c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L77.3 256 246.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-192 192z"/></svg>';
                } elseif ($item->title == "الرئيسية") {
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" style="width:15px; height:15px; position:absolute;" class="hover-image"><path style="width: 15px;" d="M310.6 233.4c12.5 12.5 12.5 32.8 0 45.3l-192 192c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3L242.7 256 73.4 86.6c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0l192 192z"/></svg>';
                }

                return '<a href="' . $item->getUrl() . '" title="" class="tw-flex tw-items-center tw-gap-3 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-tracking-tight tw-text-gray-600 tw-transition-all tw-duration-200 tw-rounded-lg tw-whitespace-nowrap hover:tw-text-gray-900 hover:tw-bg-gray-100' . $this->getActiveState($item) . '" ' . $item->getAttributes() . '>' .
                    '<div style="position:relative; display:flex; align-items:center; justify-content:center; width:35px; height:35px;">' .
                    // Default Image (Image 1)
                    '<img src="' . asset('/uploads/images/8.png') . '" class="default-image" style="width:35px; height:35px; object-fit:cover;" />' .
                    // Display SVG based on the title condition
                    $svg .
                    '</div>' .
                    ' <span class="tw-truncate">' . self::$id . '</span>' .
                    '</a>' . PHP_EOL;
            }

        }


    }

    /**
     * {@inheritdoc}.
     */
    public function getActiveState($item, $state = ' tw-bg-gray-200 tw-text-primary-700')
    {

        return $item->isActive() ? $state : null;
    }

    /**
     * Get active state on child items.
     *
     * @param $item
     * @param string $state
     * @return null|string
     */


    public function getActiveStateOnChild($item, $state = 'tw-pb-1 tw-rounded-md tw-bg-gray-200 tw-text-primary-700')
    {

        return $item->hasActiveOnChild() ? $state : null;
    }

    /**
     * {@inheritdoc}.
     */
    public function getDividerWrapper()
    {
        // Assuming a divider is just a visual space in this design
        return '<div class="tw-my-2"></div>';
    }

    /**
     * {@inheritdoc}.
     */
    public function getHeaderWrapper($item)
    {

        return '<div class="tw-px-3 tw-py-2 tw-text-xs tw-font-semibold tw-uppercase tw-tracking-wider">' . $item->title . '</div>';
    }

    /**
     * {@inheritdoc}.
     */
    public function getMenuWithDropDownWrapper($item)
    {
        if ($item->title == self::$id || self::$id == "Point of Sale") {

            $dropdownToggle = '<a href="#" title="" style=" display:none;" class="drop_down tw-flex tw-items-center tw-gap-3 tw-px-3 tw-py-2 tw-text-sm tw-font-medium tw-tracking-tight tw-text-gray-600 tw-transition-all tw-duration-200 tw-rounded-lg tw-whitespace-nowrap hover:tw-text-gray-900 hover:tw-bg-gray-100 focus:tw-text-gray-900 focus:tw-bg-gray-100' . $this->getActiveStateOnChild($item) . '" ' . $item->getAttributes() . '>' .
                $this->formatIcon($item->icon) . ' <span class="tw-truncate">' . $item->title . '</span>' .
                '<svg aria-hidden="true" class="svg tw-ml-auto tw-text-gray-500 tw-size-4 tw-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">' . $this->getArray($item) .
                '</svg>' .
                '</a>';

            $childItemsContainerStart = '';
            $childItemsContainerEnd = '';

            if (self::$id == "Point of Sale") {
                $childItems = $this->getChildMenuItems($item, "Point of Sale");
            } else {
                $childItems = $this->getChildMenuItems($item);
            }

            return '<div class="' . $this->getActiveStateOnChild($item) . '">' . $dropdownToggle . $childItemsContainerStart . $childItems . $childItemsContainerEnd . '</div>' . PHP_EOL;
        }
    }


    /**
     * Get multi-level dropdown wrapper.
     *
     * Note: This example doesn't directly implement a multi-level dropdown, as it wasn't specified, but you could extend
     * the functionality similarly to `getMenuWithDropDownWrapper`, adjusting for deeper nesting.
     *
     * @param \Nwidart\Menus\MenuItem $item
     * @return string
     */
    public function getMultiLevelDropdownWrapper($item)
    {
        // Placeholder for multi-level dropdown functionality if needed
        return '';
    }

    /**
     * Get child menu items.
     *
     * @param \Nwidart\Menus\MenuItem $item
     * @return string
     */
    public function getChildMenuItems($item, $specialValue = null)
    {
        $children = '';
        $displayStyle = $item->hasActiveOnChild() ? 'block' : 'none';

        if (count($item->getChilds()) > 0) {
            $children .= '<div class="chiled tw-relative tw-mt-2 tw-mb-4 tw-pl-11" style="padding: 0">
            <div class="tw-absolute tw-inset-y-0 tw-w-px tw-h-full tw-bg-gray-200 tw-left-5"></div>
            <div class="tw-space-y-3.5" style="display: flex;">';
            foreach ($item->getChilds() as $child) {
                // Condition for special value "Point of Sale"
                if ($specialValue == "Point of Sale" && ($child->title == "List POS" || $child->title == "POS" || $child->title == "قائمة نقطة البيع" || $child->title == "نقطة بيع")) {
                    $isActive = $child->isActive() ? 'tw-text-primary-700' : '';
                    $children .= '<a href="' . $child->getUrl() . '" title="" style="margin:0; padding-left:30px" class="tw-flex tw-text-sm tw-font-medium tw-tracking-tight tw-text-gray-600 tw-truncate tw-transition-all tw-duration-200 hover:tw-text-gray-900 tw-whitespace-nowrap ' . $isActive . '" ' . $isActive . ' ' . $child->getAttributes() . ' ' . $child->hasActiveOnChild() . '>' .
                        $child->getIcon() . ' <span>' . $child->title . '</span></a>' . PHP_EOL;
                    continue; // Skips the rest of the iteration for this child if the condition is met
                }elseif($specialValue == null){
                    // Condition for "Sell" item: Skip "List POS" and "POS"
                    if (($item->title == "Sell" || $item->title == "المبيعات") && ($child->title == "List POS" || $child->title == "POS" || $child->title == "قائمة نقطة البيع" || $child->title == "نقطة بيع")) {
                        continue;
                    }
                    elseif(($item->title == "Settings" || $item->title == "إعدادات") && ($child->title == "Barcode Settings" || $child->title == "إعدادات الباركود")){
                        continue; // Skip these items
                    }
                    // Standard active state handling and rendering child item
                    $isActive = $child->isActive() ? 'tw-text-primary-700' : '';
                    $children .= '<a href="' . $child->getUrl() . '" title="" style="margin:0; padding-left:30px" class="tw-flex tw-text-sm tw-font-medium tw-tracking-tight tw-text-gray-600 tw-truncate tw-transition-all tw-duration-200 hover:tw-text-gray-900 tw-whitespace-nowrap ' . $isActive . '" ' . $isActive . ' ' . $child->getAttributes() . ' ' . $child->hasActiveOnChild() . '>' .
                        $child->getIcon() . ' <span>' . $child->title . '</span></a>' . PHP_EOL;
                }




            }

            $children .= '</div></div>';
        }

        return $children;
    }



    /**
     * Returns the icon HTML. If the icon is SVG, it returns directly; otherwise, it assumes it's a FontAwesome class and wraps it in an <i> tag.
     *
     * @param string $icon
     * @return string
     */
    protected function formatIcon($icon)
    {
        // Check if the icon string contains "<svg", indicating it's an SVG icon
        if (strpos($icon, '<svg') !== false) {
            return $icon; // Return the SVG icon directly
        } else {
            // Assume it's a FontAwesome icon and return it wrapped in an <i> tag
            return '<i class="' . $icon . '"></i>';
        }
    }

    public function getArray($item)
    {
        if ($item->hasActiveOnChild()) {
            return '<path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M6 9l6 6l6 -6" />';
        } else {
            return '<path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M15 6l-6 6l6 6" />';
        }
    }
}


