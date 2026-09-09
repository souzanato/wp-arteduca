(function() {
  'use strict';

  let debounceTimer;

  function initFiltroArtEduca() {
    const blocks = document.querySelectorAll('.filtro-arteduca-block');
    
    blocks.forEach(function(block) {
      const apiBaseUrl = block.dataset.apiBaseUrl || 'https://bebelume.com.br';
      const selectedCategories = JSON.parse(block.dataset.selectedCategories || '[]');
      const postsLimit = parseInt(block.dataset.postsLimit) || 20;
      const searchPlaceholder = block.dataset.searchPlaceholder || 'Buscar...';
      const apiUrl = apiBaseUrl + '/api/v1/search/combined';
      
      // Criar HTML do input DENTRO do bloco
      block.innerHTML = createSearchHTML(searchPlaceholder);
      
      // Criar campos e posts FORA do bloco (depois dele)
      const camposAndPostsHTML = createCamposAndPostsHTML();
      block.insertAdjacentHTML('afterend', camposAndPostsHTML);
      
      // Inicializar ícones Lucide
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
      
      // Elementos Desktop
      const searchInput = block.querySelector('.filtro-search-input');
      const resultsContainer = block.querySelector('.filtro-search-results');
      
      // Elementos Mobile
      const mobileSearchTrigger = block.querySelector('.filtro-mobile-search-trigger');
      const mobileBackdrop = block.querySelector('.filtro-mobile-backdrop');
      const mobileSearchInput = block.querySelector('.filtro-mobile-search-input');
      const mobileClose = block.querySelector('.filtro-mobile-close');
      const mobileResults = block.querySelector('.filtro-mobile-results');
      
      // Mobile: Abrir busca
      if (mobileSearchTrigger) {
        mobileSearchTrigger.addEventListener('click', function() {
          mobileBackdrop.style.display = 'block';
          document.body.style.overflow = 'hidden';
          
          // Adicionar entrada no history para capturar botão voltar
          window.history.pushState({ filtroBackdrop: true }, '');
          
          setTimeout(function() {
            mobileSearchInput.focus();
          }, 100);
          // Reinicializar ícones
          if (typeof lucide !== 'undefined') {
            lucide.createIcons();
          }
        });
      }
      
      // Fechar backdrop (função reutilizável)
      function closeBackdrop() {
        if (mobileBackdrop && mobileBackdrop.style.display === 'block') {
          mobileBackdrop.style.display = 'none';
          document.body.style.overflow = '';
          if (mobileSearchInput) {
            mobileSearchInput.value = '';
          }
          if (mobileResults) {
            mobileResults.innerHTML = '';
          }
        }
      }
      
      // Mobile: Fechar busca pelo botão X
      if (mobileClose) {
        mobileClose.addEventListener('click', function() {
          closeBackdrop();
          // Voltar no history se foi adicionado
          if (window.history.state && window.history.state.filtroBackdrop) {
            window.history.back();
          }
        });
      }
      
      // Capturar botão voltar do navegador
      window.addEventListener('popstate', function(e) {
        closeBackdrop();
      });
      
      // Campos de Experiência - Click handler (buscar no document pois estão fora do block)
      const camposItems = document.querySelectorAll('.filtro-campo-item');
      const postsGrid = document.querySelector('.filtro-posts-grid');
      
      camposItems.forEach(function(item) {
        item.addEventListener('click', function() {
          const campo = this.getAttribute('data-campo');
          
          // Destacar campo selecionado
          camposItems.forEach(function(i) { i.classList.remove('active'); });
          this.classList.add('active');
          
          // Carregar posts do campo
          loadPostsByCampo(campo, apiUrl, selectedCategories, postsLimit, postsGrid);
        });
      });
      
      // Carregar todos os posts ao iniciar
      if (postsGrid) {
        loadPostsByCampo('', apiUrl, selectedCategories, postsLimit, postsGrid);
      }
      
      // Mobile: Buscar ao digitar
      if (mobileSearchInput) {
        mobileSearchInput.addEventListener('input', function(e) {
          const query = e.target.value.trim();
          clearTimeout(debounceTimer);
          
          if (query === '') {
            mobileResults.innerHTML = '';
            return;
          }
          
          showLoading(mobileResults);
          
          debounceTimer = setTimeout(function() {
            // Mobile: mostrar resultados no DROPDOWN (não nos cards)
            searchPosts(query, apiUrl, selectedCategories, postsLimit, mobileResults, false);
          }, 300);
        });
      }
      
      // Event listener Desktop - buscar ao digitar
      if (searchInput) {
        searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        // Limpar timer anterior
        clearTimeout(debounceTimer);
        
        if (query === '') {
          // Input vazio: recarregar posts padrão
          const postsGrid = document.querySelector('.filtro-posts-grid');
          if (postsGrid) {
            loadPostsByCampo('', apiUrl, selectedCategories, postsLimit, postsGrid);
          }
          // Limpar seleção dos campos
          document.querySelectorAll('.filtro-campo-item').forEach(function(item) {
            item.classList.remove('active');
          });
          return;
        }
        
        // Debounce: esperar 300ms antes de buscar
        debounceTimer = setTimeout(function() {
          // Desktop: mostrar resultados nos CARDS (não no dropdown)
          searchPosts(query, apiUrl, selectedCategories, postsLimit, null, true);
        }, 300);
      });
      }
      
      // Limpar input desktop ao pressionar ESC
      if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            searchInput.value = '';
            const postsGrid = document.querySelector('.filtro-posts-grid');
            if (postsGrid) {
              loadPostsByCampo('', apiUrl, selectedCategories, postsLimit, postsGrid);
            }
            document.querySelectorAll('.filtro-campo-item').forEach(function(item) {
              item.classList.remove('active');
            });
          }
        });
      }
    });
  }
  
  function createSearchHTML(placeholder) {
    // Pegar URL do plugin (será passada via localize_script)
    const pluginUrl = window.bebelumeArteducaData?.pluginUrl || '';
    
    return '<div class="filtro-arteduca-topbar">' +
      '<div class="filtro-topbar-container">' +
        // Desktop: Logo + Input (sem ícone de lupa)
        '<img src="' + pluginUrl + 'assets/images/bebelume-arteduca-150-150.jpg" alt="Bebelume ArtEduca" class="filtro-logo filtro-desktop-only">' +
        '<div class="filtro-search-wrapper filtro-desktop-only">' +
          '<input type="text" class="filtro-search-input" placeholder="' + placeholder + '">' +
        '</div>' +
        // Mobile: Logo centralizada + Lupa
        '<img src="' + pluginUrl + 'assets/images/bebelume-arteduca-150-150.jpg" alt="Bebelume ArtEduca" class="filtro-logo filtro-mobile-only filtro-mobile-logo">' +
        '<button class="filtro-mobile-search-trigger filtro-mobile-only" aria-label="Buscar">' +
          '<i data-lucide="search"></i>' +
        '</button>' +
      '</div>' +
    '</div>' +
    // Mobile Backdrop + Search
    '<div class="filtro-mobile-backdrop" style="display: none;">' +
      '<div class="filtro-mobile-search-header">' +
        '<button class="filtro-mobile-search-btn" aria-label="Buscar" disabled>' +
          '<i data-lucide="search"></i>' +
        '</button>' +
        '<input type="text" class="filtro-mobile-search-input" placeholder="' + placeholder + '">' +
        '<button class="filtro-mobile-close" aria-label="Fechar">' +
          '<i data-lucide="x"></i>' +
        '</button>' +
      '</div>' +
      '<div class="filtro-mobile-results"></div>' +
    '</div>' +
    '<div class="filtro-search-results filtro-desktop-only" style="display: none;"></div>';
  }
  
  function createCamposAndPostsHTML() {
    const pluginUrl = window.bebelumeArteducaData?.pluginUrl || '';
    
    return '<div class="filtro-campos-grid">' +
      '<div class="filtro-campo-item" data-campo="O Eu, o Outro, o Nós">' +
        '<div class="filtro-campo-circle">' +
          '<div class="filtro-campo-bg"></div>' +
          '<img src="' + pluginUrl + 'assets/images/campos/eu-outro-nos.png" alt="O Eu, o Outro, o Nós">' +
        '</div>' +
        '<div class="filtro-campo-nome">O EU, O OUTRO, O NÓS</div>' +
      '</div>' +
      '<div class="filtro-campo-item" data-campo="Escuta, Fala, Pensamento e Imaginação">' +
        '<div class="filtro-campo-circle">' +
          '<div class="filtro-campo-bg"></div>' +
          '<img src="' + pluginUrl + 'assets/images/campos/escuta-fala.png" alt="Escuta, Fala, Pensamento e Imaginação">' +
        '</div>' +
        '<div class="filtro-campo-nome">ESCUTA, FALA, PENSAMENTO E IMAGINAÇÃO</div>' +
      '</div>' +
      '<div class="filtro-campo-item" data-campo="Espaço, Tempos, Quantidades, Relações e Transformações">' +
        '<div class="filtro-campo-circle">' +
          '<div class="filtro-campo-bg"></div>' +
          '<img src="' + pluginUrl + 'assets/images/campos/espaco-tempos.png" alt="Espaço, Tempos, Quantidades">' +
        '</div>' +
        '<div class="filtro-campo-nome">ESPAÇO, TEMPOS, QUANTIDADES, RELAÇÕES E TRANSFORMAÇÕES</div>' +
      '</div>' +
      '<div class="filtro-campo-item" data-campo="Corpo, Gestos e Movimentos">' +
        '<div class="filtro-campo-circle">' +
          '<div class="filtro-campo-bg"></div>' +
          '<img src="' + pluginUrl + 'assets/images/campos/corpo-gestos.png" alt="Corpo, Gestos e Movimentos">' +
        '</div>' +
        '<div class="filtro-campo-nome">CORPO, GESTOS E MOVIMENTOS</div>' +
      '</div>' +
      '<div class="filtro-campo-item" data-campo="Traços, Sons, Cores e Formas">' +
        '<div class="filtro-campo-circle">' +
          '<div class="filtro-campo-bg"></div>' +
          '<img src="' + pluginUrl + 'assets/images/campos/tracos-sons.png" alt="Traços, Sons, Cores e Formas">' +
        '</div>' +
        '<div class="filtro-campo-nome">TRAÇOS, SONS, CORES E FORMAS</div>' +
      '</div>' +
      '<div class="filtro-campo-item" data-campo="">' +
        '<div class="filtro-campo-circle">' +
          '<div class="filtro-campo-bg"></div>' +
          '<img src="' + pluginUrl + 'assets/images/campos/todos.png" alt="Todos">' +
        '</div>' +
        '<div class="filtro-campo-nome">TODOS</div>' +
      '</div>' +
    '</div>' +
    '<div class="filtro-posts-container">' +
      '<div class="filtro-posts-grid"></div>' +
    '</div>';
  }
  
  function showLoading(container) {
    container.style.display = 'block';
    container.innerHTML = '<div class="filtro-search-loading">' +
      '<div class="filtro-spinner"></div>' +
      '<p>Buscando...</p>' +
    '</div>';
  }
  
  function searchPosts(query, apiUrl, categories, limit, container, showInCards) {
    const params = new URLSearchParams();
    params.append('q', query);
    if (categories.length > 0) params.append('category_ids', categories.join(','));
    params.append('limit', limit);
    
    // Se showInCards = true, mostra nos cards, senão mostra no dropdown
    if (showInCards) {
      const postsGrid = document.querySelector('.filtro-posts-grid');
      if (postsGrid) {
        postsGrid.innerHTML = '<div class="filtro-cards-loading">' +
          '<div class="filtro-spinner"></div>' +
          '<p>Buscando...</p>' +
        '</div>';
      }
    }
    
    fetch(apiUrl + '?' + params.toString())
      .then(function(response) {
        if (!response.ok) throw new Error('Erro na API: ' + response.status);
        return response.json();
      })
      .then(function(data) {
        if (showInCards) {
          // Mostrar nos cards
          const postsGrid = document.querySelector('.filtro-posts-grid');
          if (postsGrid) {
            console.log('🔍 Busca por "' + query + '":', data.total_results, 'resultados');
            renderPostCards(data.results || [], postsGrid);
            
            // Limpar seleção dos campos
            document.querySelectorAll('.filtro-campo-item').forEach(function(item) {
              item.classList.remove('active');
            });
          }
        } else {
          // Mostrar no dropdown (mobile)
          renderDropdown(query, data.results || [], container);
        }
      })
      .catch(function(error) {
        if (showInCards) {
          const postsGrid = document.querySelector('.filtro-posts-grid');
          if (postsGrid) {
            postsGrid.innerHTML = '<div class="filtro-cards-error">' +
              '<p>Erro ao buscar: ' + error.message + '</p>' +
            '</div>';
          }
        } else {
          showError(container, error.message);
        }
      });
  }
  
  function renderDropdown(query, results, container) {
    container.style.display = 'block';
    
    var html = '';
    
    // Sugestões de busca (primeiras 5)
    var suggestions = generateSuggestions(query, results);
    if (suggestions.length > 0) {
      html += '<div class="filtro-dropdown-section">';
      suggestions.forEach(function(suggestion) {
        html += '<div class="filtro-suggestion-item">' +
          '<i data-lucide="search" class="filtro-suggestion-icon"></i>' +
          '<span class="filtro-suggestion-text">' + suggestion + '</span>' +
        '</div>';
      });
      html += '</div>';
    }
    
    // Resultados com imagem
    if (results.length > 0) {
      html += '<div class="filtro-dropdown-section">';
      html += '<div class="filtro-section-title">Posts</div>';
      
      results.slice(0, 8).forEach(function(post) {
        var imageHtml = post.featured_image
          ? '<img src="' + post.featured_image + '" alt="' + post.title + '" class="filtro-dropdown-image">'
          : '<div class="filtro-dropdown-image filtro-dropdown-image-placeholder">' +
            '<i data-lucide="file-text" class="filtro-placeholder-icon"></i>' +
            '</div>';
        
        html += '<a href="' + post.url + '" class="filtro-dropdown-result">' +
          imageHtml +
          '<div class="filtro-dropdown-content">' +
            '<div class="filtro-dropdown-title">' + post.title + '</div>' +
            (post.excerpt ? '<div class="filtro-dropdown-excerpt">' + truncate(post.excerpt, 80) + '</div>' : '') +
          '</div>' +
        '</a>';
      });
      
      html += '</div>';
    }
    
    if (html === '') {
      container.innerHTML = '<div class="filtro-search-empty">' +
        '<i data-lucide="search-x" style="width:48px;height:48px;margin-bottom:1rem;opacity:0.5;"></i>' +
        '<p>Nenhum resultado encontrado</p>' +
      '</div>';
    } else {
      container.innerHTML = html;
    }
    
    // Reinicializar ícones Lucide
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  }
  
  function generateSuggestions(query, results) {
    var suggestions = [];
    
    // Sugestão 1: Termo exato
    suggestions.push(query);
    
    // Sugestões baseadas nos títulos dos resultados (máximo 4)
    results.slice(0, 4).forEach(function(post) {
      var title = post.title.toLowerCase();
      if (title.indexOf(query.toLowerCase()) !== -1 && suggestions.indexOf(post.title) === -1) {
        suggestions.push(post.title);
      }
    });
    
    return suggestions.slice(0, 5);
  }
  
  function showError(container, message) {
    container.style.display = 'block';
    container.innerHTML = '<div class="filtro-search-error">' +
      '<i data-lucide="alert-circle" style="width:48px;height:48px;margin-bottom:1rem;color:#f44336;"></i>' +
      '<p>Erro ao buscar: ' + message + '</p>' +
    '</div>';
    
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  }
  
  function truncate(text, maxLength) {
    if (text.length <= maxLength) return text;
    return text.substr(0, maxLength) + '...';
  }
  
  function loadPostsByCampo(campo, apiUrl, categories, limit, container) {
    const params = new URLSearchParams();
    // IMPORTANTE: Não usar .append() pois ele faz encode automático
    // Montar URL manualmente para preservar o nome exato do campo
    let url = apiUrl + '?';
    
    if (campo) {
      // Usar o campo exatamente como está (vírgulas incluídas)
      url += 'campos=' + encodeURIComponent(campo) + '&';
    }
    
    if (categories.length > 0) {
      url += 'category_ids=' + categories.join(',') + '&';
    }
    
    url += 'limit=' + limit;
    
    console.log('🔍 Buscando posts:', {
      campo: campo,
      url: url
    });
    
    // Mostrar loading
    container.innerHTML = '<div class="filtro-cards-loading">' +
      '<div class="filtro-spinner"></div>' +
      '<p>Carregando grupos de atividades...</p>' +
    '</div>';
    
    fetch(url)
      .then(function(response) {
        if (!response.ok) throw new Error('Erro na API: ' + response.status);
        return response.json();
      })
      .then(function(data) {
        console.log('✅ Resposta da API:', data);
        console.log('📊 Filtros aplicados:', data.filters);
        console.log('📝 Total de resultados:', data.total_results);
        renderPostCards(data.results || [], container);
      })
      .catch(function(error) {
        console.error('❌ Erro:', error);
        container.innerHTML = '<div class="filtro-cards-error">' +
          '<p>Erro ao carregar posts: ' + error.message + '</p>' +
        '</div>';
      });
  }
  
  function renderPostCards(posts, container) {
    if (posts.length === 0) {
      container.innerHTML = '<div class="filtro-cards-empty">' +
        '<i data-lucide="folder-open" style="width:64px;height:64px;margin-bottom:1rem;opacity:0.3;"></i>' +
        '<p>Nenhum post encontrado</p>' +
      '</div>';
      
      if (typeof lucide !== 'undefined') {
        lucide.createIcons();
      }
      return;
    }

    // hasAccess vem do PHP como string "1" (tem acesso) ou "0" (sem acesso)
    const hasAccess = window.bebelumeArteducaData && window.bebelumeArteducaData.hasAccess == 1;
    const plansUrl  = (window.bebelumeArteducaData && window.bebelumeArteducaData.plansUrl) || '/planos/';
    
    container.innerHTML = posts.map(function(post) {
      const imageHtml = post.featured_image
        ? '<img src="' + post.featured_image + '" alt="' + post.title + '" class="filtro-card-image">'
        : '<div class="filtro-card-image filtro-card-image-placeholder">' +
          '<i data-lucide="image" style="width:48px;height:48px;opacity:0.2;"></i>' +
          '</div>';

      if ( hasAccess ) {
        // Usuário com acesso — card normal
        return '<a href="' + post.url + '" class="filtro-post-card">' +
          imageHtml +
          '<div class="filtro-card-content">' +
            '<h3 class="filtro-card-title">' + post.title + '</h3>' +
            (post.excerpt ? '<p class="filtro-card-excerpt">' + truncate(post.excerpt, 120) + '</p>' : '') +
          '</div>' +
        '</a>';
      } else {
        // Sem acesso — card com cadeado no hover + redireciona para planos
        return '<a href="' + plansUrl + '" class="filtro-post-card filtro-post-card--locked" title="Conteúdo exclusivo para assinantes">' +
          '<div class="filtro-card-locked-wrap">' +
            imageHtml +
            '<div class="filtro-card-lock-overlay">' +
              '<div class="filtro-card-lock-icon">' +
                '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
                  '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>' +
                  '<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>' +
                '</svg>' +
              '</div>' +
              '<span class="filtro-card-lock-text">Conteúdo exclusivo</span>' +
            '</div>' +
          '</div>' +
          '<div class="filtro-card-content">' +
            '<h3 class="filtro-card-title">' + post.title + '</h3>' +
          '</div>' +
        '</a>';
      }
    }).join('');
    
    // Inicializar ícones Lucide
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  }
  
  // Inicializar quando DOM carregar
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFiltroArtEduca);
  } else {
    initFiltroArtEduca();
  }
  
})();
