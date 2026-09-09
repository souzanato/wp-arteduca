/**
 * Bloco: Filtro ArtEduca
 * Busca e filtragem de posts por campos de experiência
 */

(function() {
  'use strict';
  
  const { registerBlockType } = wp.blocks;
  const { InspectorControls } = wp.blockEditor;
  const { PanelBody, TextControl, CheckboxControl } = wp.components;
  const { createElement: el, Fragment } = wp.element;
  const { useState, useEffect } = wp.element;
  const { useSelect } = wp.data;

  // Definição dos campos de experiência (conforme BNCC)
  const CAMPOS_EXPERIENCIA = [
    { id: 'eu-outro-nos', nome: 'O Eu, o Outro, o Nós', icon: '👥' },
    { id: 'escuta-fala', nome: 'Escuta, Fala, Pensamento e Imaginação', icon: '👂' },
    { id: 'espaco-tempos', nome: 'Espaço, Tempos, Quantidades, Relações e Transformações', icon: '⏰' },
    { id: 'corpo-gestos', nome: 'Corpos, Gestos e Movimentos', icon: '✋' },
    { id: 'tracos-sons', nome: 'Traços, Sons, Cores e Formas', icon: '🎨' }
  ];

  registerBlockType('bebelume/filtro-arteduca', {
    edit: function(props) {
      const { attributes, setAttributes } = props;
      const { apiBaseUrl, selectedCategories, postsLimit, searchPlaceholder } = attributes;

      // Buscar categorias do WordPress
      const categories = useSelect(function(select) {
        return select('core').getEntityRecords('taxonomy', 'category', { per_page: -1 }) || [];
      }, []);

      // Toggle categoria
      const toggleCategory = function(categoryId) {
        const isSelected = selectedCategories.indexOf(categoryId) !== -1;
        const newSelected = isSelected
          ? selectedCategories.filter(function(id) { return id !== categoryId; })
          : selectedCategories.concat([categoryId]);
        setAttributes({ selectedCategories: newSelected });
      };

      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: 'Configurações da API', initialOpen: true },
            el(TextControl, {
              label: 'URL Base da API',
              value: apiBaseUrl,
              onChange: function(value) { setAttributes({ apiBaseUrl: value }); },
              help: 'Ex: https://seu-dominio.com.br (sem barra final)',
              placeholder: 'https://bebelume.com.br'
            }),
            el('p', { style: { fontSize: '0.85rem', color: '#666', margin: '0.5rem 0 1rem' } }, 
              el('strong', null, 'Endpoint usado:'),
              el('br'),
              (apiBaseUrl || 'https://bebelume.com.br') + '/api/v1/search/combined'
            ),
            el(TextControl, {
              label: 'Limite de Posts',
              type: 'number',
              value: postsLimit,
              onChange: function(value) { setAttributes({ postsLimit: parseInt(value) }); }
            }),
            el(TextControl, {
              label: 'Placeholder do Campo de Busca',
              value: searchPlaceholder,
              onChange: function(value) { setAttributes({ searchPlaceholder: value }); },
              placeholder: 'Buscar...'
            })
          ),
          el(
            PanelBody,
            { title: 'Categorias', initialOpen: false },
            el('p', { style: { fontSize: '0.9rem', marginBottom: '1rem' } },
              'Selecione as categorias para filtrar. Deixe vazio para mostrar todas.'
            ),
            categories.length === 0
              ? el('p', { style: { color: '#666', fontStyle: 'italic' } }, 'Carregando categorias...')
              : categories.map(function(cat) {
                  return el(CheckboxControl, {
                    key: cat.id,
                    label: cat.name + ' (' + cat.count + ')',
                    checked: selectedCategories.indexOf(cat.id) !== -1,
                    onChange: function() { toggleCategory(cat.id); }
                  });
                })
          )
        ),
        el(
          'div',
          { className: 'wp-block-bebelume-filtro-arteduca' },
          el(
            'div',
            { style: { padding: '2rem', background: '#f0f0f0', borderRadius: '8px' } },
            el('h3', { style: { textAlign: 'center', marginBottom: '1.5rem' } }, '🔍 Filtro ArtEduca'),
            
            // Mock do input de busca
            el(
              'div',
              { style: { 
                position: 'relative',
                marginBottom: '1.5rem',
                background: '#fff',
                border: '2px solid #e0e0e0',
                borderRadius: '12px',
                padding: '0.875rem 1rem 0.875rem 3rem',
                fontSize: '1rem',
                color: '#999'
              }},
              el('span', { style: { position: 'absolute', left: '1rem', top: '50%', transform: 'translateY(-50%)' } }, '🔍'),
              searchPlaceholder || 'Buscar...'
            ),
            
            el('p', { style: { color: '#666', marginBottom: '1rem', textAlign: 'center' } },
              'Preview do bloco - Configure no painel à direita →'
            ),
            el(
              'div',
              { style: { background: '#fff', padding: '1rem', borderRadius: '4px', marginTop: '1rem', textAlign: 'left' } },
              el('p', { style: { margin: 0, fontSize: '0.875rem' } },
                el('strong', null, 'URL Base: '),
                apiBaseUrl || '(não configurada)',
                el('br'),
                el('strong', null, 'Placeholder: '),
                searchPlaceholder || 'Buscar...',
                el('br'),
                el('strong', null, 'Categorias: '),
                selectedCategories.length === 0 
                  ? 'Todas' 
                  : selectedCategories.length + ' selecionada(s)',
                el('br'),
                el('strong', null, 'Limite: '),
                postsLimit + ' posts'
              )
            ),
            !apiBaseUrl && el(
              'div',
              { style: { background: '#fff3cd', border: '1px solid #ffc107', borderRadius: '4px', padding: '1rem', marginTop: '1rem' } },
              el('p', { style: { margin: 0, fontSize: '0.875rem', color: '#856404' } },
                '⚠️ Configure a URL Base da API no painel à direita.'
              )
            )
          )
        )
      );
    },

    save: function({ attributes }) {
      return el('div', {
        className: 'filtro-arteduca-block',
        'data-api-base-url': attributes.apiBaseUrl,
        'data-selected-categories': JSON.stringify(attributes.selectedCategories),
        'data-posts-limit': attributes.postsLimit,
        'data-search-placeholder': attributes.searchPlaceholder
      });
    }
  });

  // FRONTEND
  if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', function() {
      const blocks = document.querySelectorAll('.filtro-arteduca-block');
      
      blocks.forEach(function(block) {
        const apiBaseUrl = block.dataset.apiBaseUrl || 'https://bebelume.com.br';
        const selectedCategories = JSON.parse(block.dataset.selectedCategories || '[]');
        const postsLimit = parseInt(block.dataset.postsLimit) || 20;
        const apiUrl = apiBaseUrl + '/api/v1/search/combined';
        
        if (wp.element.render) {
          wp.element.render(
            el(FiltroArtEducaFrontend, {
              apiUrl: apiUrl,
              selectedCategories: selectedCategories,
              postsLimit: postsLimit
            }),
            block
          );
        }
      });
    });
  }

  function FiltroArtEducaFrontend(props) {
    const apiUrl = props.apiUrl;
    const selectedCategories = props.selectedCategories;
    const postsLimit = props.postsLimit;
    
    const [searchQuery, setSearchQuery] = useState('');
    const [selectedCampos, setSelectedCampos] = useState([]);
    const [posts, setPosts] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    const fetchPosts = function(query, campos) {
      query = query || '';
      campos = campos || [];
      setLoading(true);
      setError(null);

      const params = new URLSearchParams();
      if (query) params.append('q', query);
      if (selectedCategories.length > 0) params.append('category_ids', selectedCategories.join(','));
      if (campos.length > 0) params.append('campos', campos.join('|'));
      params.append('limit', postsLimit);

      fetch(apiUrl + '?' + params.toString())
        .then(function(r) { if (!r.ok) throw new Error('Erro: ' + r.status); return r.json(); })
        .then(function(data) { setPosts(data.results || []); setLoading(false); })
        .catch(function(err) { setError(err.message); setPosts([]); setLoading(false); });
    };

    useEffect(function() { fetchPosts('', []); }, []);

    const handleSearch = function(e) { e.preventDefault(); fetchPosts(searchQuery, selectedCampos); };
    const toggleCampo = function(nome) {
      if (nome === 'TODOS') { setSelectedCampos([]); fetchPosts(searchQuery, []); return; }
      const isSelected = selectedCampos.indexOf(nome) !== -1;
      const newSelected = isSelected ? selectedCampos.filter(function(c) { return c !== nome; }) : selectedCampos.concat([nome]);
      setSelectedCampos(newSelected);
      fetchPosts(searchQuery, newSelected);
    };

    return el('div', { className: 'wp-block-bebelume-filtro-arteduca' },
      el('div', { className: 'filtro-arteduca-header' },
        el('div', { className: 'filtro-arteduca-colorbar' }),
        el('h2', { className: 'filtro-arteduca-title' }, 'Campos da Experiência')
      ),
      el('form', { className: 'filtro-arteduca-search', onSubmit: handleSearch },
        el('input', {
          type: 'text',
          className: 'filtro-arteduca-search-input',
          placeholder: 'PERQUISA POR PALAVRA CHAVE',
          value: searchQuery,
          onChange: function(e) { setSearchQuery(e.target.value); }
        }),
        el('button', { type: 'submit', className: 'filtro-arteduca-search-button', 'aria-label': 'Buscar' },
          el('svg', { className: 'filtro-arteduca-search-icon', fill: 'none', viewBox: '0 0 24 24' },
            el('circle', { cx: '11', cy: '11', r: '8' }),
            el('path', { d: 'm21 21-4.35-4.35' })
          )
        )
      ),
      el('div', { className: 'filtro-arteduca-campos-section' },
        el('h3', { className: 'filtro-arteduca-campos-title' }, 'PERQUISA POR CAMPO'),
        el('div', { className: 'filtro-arteduca-campos-grid' },
          CAMPOS_EXPERIENCIA.map(function(campo) {
            const isActive = selectedCampos.indexOf(campo.nome) !== -1;
            return el('div', {
              key: campo.id,
              className: 'filtro-arteduca-campo' + (isActive ? ' active' : ''),
              onClick: function() { toggleCampo(campo.nome); }
            },
              el('div', { className: 'filtro-arteduca-campo-icon' }, campo.icon),
              el('div', { className: 'filtro-arteduca-campo-text' }, campo.nome)
            );
          }).concat([
            el('div', {
              key: 'todos',
              className: 'filtro-arteduca-campo todos' + (selectedCampos.length === 0 ? ' active' : ''),
              onClick: function() { toggleCampo('TODOS'); }
            },
              el('div', { className: 'filtro-arteduca-campo-icon' }, '📚'),
              el('div', { className: 'filtro-arteduca-campo-text' }, 'TODOS')
            )
          ])
        )
      ),
      loading ? el('div', { className: 'filtro-arteduca-loading' }, el('div', { className: 'filtro-arteduca-spinner' }))
        : error ? el('div', { className: 'filtro-arteduca-no-results' }, 'Erro: ' + error)
        : posts.length === 0 ? el('div', { className: 'filtro-arteduca-no-results' }, 'Nenhum post encontrado.')
        : el('div', { className: 'filtro-arteduca-posts-grid' },
            posts.map(function(post) {
              return el('a', { key: post.wordpress_post_id, href: post.url, className: 'filtro-arteduca-post-card' },
                post.featured_image
                  ? el('img', { src: post.featured_image, alt: post.title, className: 'filtro-arteduca-post-image' })
                  : el('div', { className: 'filtro-arteduca-post-image' }),
                el('div', { className: 'filtro-arteduca-post-content' },
                  el('h3', { className: 'filtro-arteduca-post-title' }, post.title),
                  post.excerpt && el('p', { className: 'filtro-arteduca-post-excerpt' }, post.excerpt)
                )
              );
            })
          )
    );
  }
})();
