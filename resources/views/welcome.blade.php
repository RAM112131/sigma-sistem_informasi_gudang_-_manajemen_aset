<!DOCTYPE html>
<html lang="id">
@include('welcome.partials.head')
<body>
  <div class="site-shell">
    @include('welcome.partials.header')
    <main id="main-content">
      @include('welcome.partials.hero')
      @include('welcome.partials.modules')
      @include('welcome.partials.workflow')
    </main>
    @include('welcome.partials.footer')
  </div>
  @include('welcome.partials.scripts')
  @include('welcome.partials.additional-styles')
</body>
</html>