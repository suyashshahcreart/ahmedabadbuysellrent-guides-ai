@if(!empty($faqs) && $faqs->count())
<section class="section">
  <div class="container">
    <h2>Frequently asked questions</h2>
    <div class="faq-list">
      @foreach($faqs as $faq)
        <details class="faq-item">
          <summary>{{ $faq->question }}</summary>
          <div class="content">{!! $faq->answer !!}</div>
        </details>
      @endforeach
    </div>
  </div>
</section>
@if(!empty($faqLd))
<script type="application/ld+json">{!! json_encode($faqLd, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endif
@endif
