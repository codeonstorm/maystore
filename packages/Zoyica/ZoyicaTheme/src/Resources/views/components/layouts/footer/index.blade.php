{!! view_render_event('bagisto.shop.layout.footer.before') !!}

@inject('themeCustomizationRepository', 'Webkul\Theme\Repositories\ThemeCustomizationRepository')

@php
    $channel = core()->getCurrentChannel();

    $customization = $themeCustomizationRepository->findOneWhere([
        'type'       => 'footer_links',
        'status'     => 1,
        'theme_code' => $channel->theme,
        'channel_id' => $channel->id,
    ]);
@endphp

<footer class="mt-9 bg-navyBlue max-sm:mt-10">

    <!-- Main Footer Body -->
    <div class="flex flex-wrap justify-between gap-x-10 gap-y-10 px-[60px] py-14 max-1060:flex-col max-md:gap-8 max-md:px-8 max-md:py-10 max-sm:px-4 max-sm:py-8">

        <!-- Brand Section -->
        <div class="flex flex-col gap-3">
            <span class="text-xl font-bold tracking-wide text-white">
                ANKIT
                {{ core()->getConfigData('general.general.locale_info.shop_title') ?? config('app.name') }}
            </span>

            <p class="max-w-[200px] text-xs leading-relaxed text-white/50 max-1060:max-w-full">
                @lang('shop::app.components.layouts.footer.subscribe-stay-touch')
            </p>
        </div>

        <!-- For Desktop View -->
        <div
            class="flex flex-wrap items-start gap-20 max-1180:gap-10 max-1060:hidden"
            v-pre
        >
            @if ($customization?->options)
                @foreach ($customization->options as $footerLinkSection)
                    <ul class="grid gap-3 text-sm">
                        @php
                            usort($footerLinkSection, function ($a, $b) {
                                return $a['sort_order'] - $b['sort_order'];
                            });
                        @endphp

                        @foreach ($footerLinkSection as $link)
                            <li>
                                <a
                                    href="{{ $link['url'] }}"
                                    class="text-white/60 transition-colors duration-200 hover:text-white hover:underline underline-offset-4"
                                >
                                    {{ $link['title'] }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            @endif
        </div>

        <!-- For Mobile View -->
        <x-shop::accordion
            :is-active="false"
            class="hidden !w-full rounded-xl !border !border-white/15 max-1060:block max-sm:rounded-lg"
        >
            <x-slot:header class="rounded-t-lg bg-white/10 font-medium text-white max-md:p-2.5 max-sm:px-3 max-sm:py-2 max-sm:text-sm">
                @lang('shop::app.components.layouts.footer.footer-content')
            </x-slot>

            <x-slot:content class="flex justify-between !bg-white/5 !p-4">
                @if ($customization?->options)
                    @foreach ($customization->options as $footerLinkSection)
                        <ul
                            class="grid gap-3 text-sm"
                            v-pre
                        >
                            @php
                                usort($footerLinkSection, function ($a, $b) {
                                    return $a['sort_order'] - $b['sort_order'];
                                });
                            @endphp

                            @foreach ($footerLinkSection as $link)
                                <li>
                                    <a
                                        href="{{ $link['url'] }}"
                                        class="text-white/60 transition-colors duration-200 hover:text-white text-sm font-medium max-sm:text-xs"
                                    >
                                        {{ $link['title'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                @endif
            </x-slot>
        </x-shop::accordion>

        {!! view_render_event('bagisto.shop.layout.footer.newsletter_subscription.before') !!}

        <!-- Newsletter Subscription -->
        @if (core()->getConfigData('customer.settings.newsletter.subscription'))
            <div class="grid gap-3 max-w-[420px] max-1060:max-w-full">
                <p
                    class="text-2xl font-semibold leading-snug text-white max-md:text-xl max-sm:text-lg"
                    role="heading"
                    aria-level="2"
                >
                    @lang('shop::app.components.layouts.footer.newsletter-text')
                </p>

                <p class="text-xs text-white/50">
                    @lang('shop::app.components.layouts.footer.subscribe-stay-touch')
                </p>

                <div class="mt-1">
                    <x-shop::form
                        :action="route('shop.subscription.store')"
                        class="rounded max-sm:mt-0"
                    >
                        <div class="relative w-full">
                            <x-shop::form.control-group.control
                                type="email"
                                class="block w-[380px] max-w-full rounded-xl border border-white/20 bg-white/10 px-5 py-4 text-base text-white placeholder-white/30 outline-none focus:border-white/40 focus:bg-white/15 max-1060:w-full max-md:p-3.5 max-sm:mb-0 max-sm:rounded-lg max-sm:p-2.5 max-sm:text-sm"
                                name="email"
                                rules="required|email"
                                label="Email"
                                :aria-label="trans('shop::app.components.layouts.footer.email')"
                                placeholder="email@example.com"
                            />

                            <x-shop::form.control-group.error control-name="email" />

                            <button
                                type="submit"
                                class="absolute top-1.5 flex w-max items-center rounded-xl bg-[#F97316] px-7 py-2.5 font-semibold text-white transition-colors duration-200 hover:bg-orange-400 ltr:right-2 rtl:left-2 max-md:top-1 max-md:px-5 max-md:text-xs max-sm:mt-0 max-sm:rounded-lg max-sm:px-4 max-sm:py-2"
                            >
                                @lang('shop::app.components.layouts.footer.subscribe')
                            </button>
                        </div>
                    </x-shop::form>
                </div>
            </div>
        @endif

        {!! view_render_event('bagisto.shop.layout.footer.newsletter_subscription.after') !!}
    </div>

    <!-- Footer Bottom Bar -->
    <div class="flex items-center justify-between border-t border-white/10 px-[60px] py-4 max-md:flex-col max-md:gap-2 max-md:justify-center max-sm:px-5">
        {!! view_render_event('bagisto.shop.layout.footer.footer_text.before') !!}

        <p class="text-sm text-white/40 max-md:text-center">
            @if (core()->getConfigData('general.content.footer.copyright_content'))
                {!! core()->getConfigData('general.content.footer.copyright_content') !!}
            @else
                @lang('shop::app.components.layouts.footer.footer-text', ['current_year'=> date('Y') ])
            @endif
        </p>

        {!! view_render_event('bagisto.shop.layout.footer.footer_text.after') !!}
    </div>

</footer>

{!! view_render_event('bagisto.shop.layout.footer.after') !!}
