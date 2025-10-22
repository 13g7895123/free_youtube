# Tasks: YouTube Loop Player

**Input**: Design documents from `/specs/001-youtube-loop-player/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, contracts/

**Tests**: This project follows Test-Driven Development (TDD) as per constitution requirement II. All tests MUST be written before implementation (Red-Green-Refactor cycle).

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Web app**: `src/`, `tests/` at repository root (single frontend project)
- Paths shown below follow Vue.js web application structure

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and basic structure

- [ ] T001 Create project directory structure per implementation plan
- [ ] T002 Initialize Node.js project with package.json (name: youtube-loop-player, type: module)
- [ ] T003 [P] Install Vue.js 3.x dependency (npm install vue@^3.3.0)
- [ ] T004 [P] Install Vite build tool (npm install -D vite@^5.0.0 @vitejs/plugin-vue)
- [ ] T005 [P] Install Vitest testing framework (npm install -D vitest@^1.0.0 @vitest/ui jsdom)
- [ ] T006 [P] Install Playwright E2E testing (npm install -D @playwright/test)
- [ ] T007 [P] Install ESLint and Prettier (npm install -D eslint@^8.0.0 prettier@^3.0.0 eslint-plugin-vue)
- [ ] T008 Configure Vite in vite.config.js (Vue plugin, build target ES2020, dev server port 3000)
- [ ] T009 Configure Vitest in vitest.config.js (jsdom environment, coverage settings, 80% threshold)
- [ ] T010 [P] Configure ESLint in .eslintrc.js (Vue 3 rules, ES2020 parser)
- [ ] T011 [P] Configure Prettier in .prettierrc (single quotes, semi, trailing comma)
- [ ] T012 Create public/index.html entry file (basic HTML5 template with root div)
- [ ] T013 Create src/main.js application entry point (import Vue, create app, mount to #app)
- [ ] T014 Create src/App.vue root component skeleton (template, script setup, style)
- [ ] T015 Create src/style.css global styles (CSS reset, basic layout variables)
- [ ] T016 Add npm scripts to package.json (dev, build, preview, test, test:watch, test:coverage, lint, format)
- [ ] T017 Create .gitignore file (node_modules, dist, coverage, .env.local)
- [ ] T018 Verify dev server starts successfully (npm run dev should launch without errors)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [ ] T019 [P] Create src/utils/errorMessages.js with all error message constants in Traditional Chinese
- [ ] T020 Create tests/unit/urlValidator.test.js with test cases for all YouTube URL formats (standard, short, embed, playlist)
- [ ] T021 Create src/utils/urlValidator.js implementing isValidYouTubeUrl, extractVideoId, extractPlaylistId functions
- [ ] T022 Create tests/unit/useLocalStorage.test.js with test cases for get, set, watch, error handling
- [ ] T023 Create src/composables/useLocalStorage.js implementing LocalStorage persistence with Vue reactivity
- [ ] T024 Verify all foundational tests pass (npm run test tests/unit/urlValidator.test.js tests/unit/useLocalStorage.test.js)

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Paste and Play YouTube Video (Priority: P1) 🎯 MVP

**Goal**: 使用者能夠貼上 YouTube 網址並立即播放影片

**Independent Test**: 貼上有效的 YouTube 網址，驗證影片在 3 秒內開始播放

### Tests for User Story 1 (TDD - WRITE TESTS FIRST) ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [ ] T025 [P] [US1] Contract test for YouTube IFrame API onReady event in tests/contract/youtube-iframe-api.test.js
- [ ] T026 [P] [US1] Contract test for YouTube IFrame API onStateChange event in tests/contract/youtube-iframe-api.test.js
- [ ] T027 [P] [US1] Contract test for YouTube IFrame API onError event in tests/contract/youtube-iframe-api.test.js
- [ ] T028 [P] [US1] Unit test for useUrlParser composable in tests/unit/useUrlParser.test.js
- [ ] T029 [P] [US1] Unit test for useYouTubePlayer composable (mock API) in tests/unit/useYouTubePlayer.test.js
- [ ] T030 [P] [US1] Integration test for player lifecycle (load, play, stop) in tests/integration/player-lifecycle.test.js
- [ ] T031 [US1] Verify all User Story 1 tests fail initially (RED phase)

### Implementation for User Story 1

- [ ] T032 [US1] Create src/composables/useUrlParser.js implementing parseYouTubeUrl, extractVideoId, isValidYouTubeUrl
- [ ] T033 [US1] Create src/composables/useYouTubePlayer.js implementing YouTube IFrame API integration (initPlayer, loadVideo, play, pause)
- [ ] T034 [US1] Implement loadYouTubeAPI function in useYouTubePlayer.js (dynamic script loading, onYouTubeIframeAPIReady callback)
- [ ] T035 [US1] Implement player initialization logic in useYouTubePlayer.js (new YT.Player with event handlers)
- [ ] T036 [US1] Implement onPlayerReady event handler in useYouTubePlayer.js
- [ ] T037 [US1] Implement onStateChange event handler in useYouTubePlayer.js (update videoSession state)
- [ ] T038 [US1] Implement onError event handler in useYouTubePlayer.js (map error codes to messages)
- [ ] T039 [P] [US1] Create src/components/UrlInput.vue (input field, submit button, validation)
- [ ] T040 [P] [US1] Create src/components/VideoPlayer.vue (YouTube player container, loading indicator)
- [ ] T041 [P] [US1] Create src/components/ErrorMessage.vue (error display with close button, role="alert")
- [ ] T042 [US1] Integrate components in src/App.vue (UrlInput, VideoPlayer, ErrorMessage layout)
- [ ] T043 [US1] Wire UrlInput submit event to useYouTubePlayer.loadVideo in App.vue
- [ ] T044 [US1] Wire useYouTubePlayer state to VideoPlayer and ErrorMessage components in App.vue
- [ ] T045 [US1] Run all User Story 1 tests and verify they pass (GREEN phase)
- [ ] T046 [US1] Refactor code if needed while keeping tests green (REFACTOR phase)
- [ ] T047 [US1] Verify test coverage meets 80% threshold for US1 files (npm run test:coverage)

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently. User can paste URL and play video.

---

## Phase 4: User Story 2 - Automatic Loop Playback (Priority: P1)

**Goal**: 影片結束時自動從頭開始循環播放

**Independent Test**: 播放短影片（30 秒），觀察影片至少循環播放 2 次無需使用者介入

### Tests for User Story 2 (TDD - WRITE TESTS FIRST) ⚠️

- [ ] T048 [P] [US2] Unit test for single video loop logic in tests/unit/useYouTubePlayer.test.js
- [ ] T049 [P] [US2] Integration test for video end detection and loop restart in tests/integration/video-loop.test.js
- [ ] T050 [US2] Verify all User Story 2 tests fail initially (RED phase)

### Implementation for User Story 2

- [ ] T051 [US2] Extend onStateChange handler in useYouTubePlayer.js to detect YT.PlayerState.ENDED (state 0)
- [ ] T052 [US2] Implement handleVideoEnd function in useYouTubePlayer.js (check loop enabled, seekTo(0), playVideo())
- [ ] T053 [US2] Add loopEnabled reactive ref to useYouTubePlayer.js (default true per spec)
- [ ] T054 [US2] Load loopEnabled from UserPreferences (useLocalStorage) in App.vue
- [ ] T055 [US2] Pass loopEnabled to useYouTubePlayer composable in App.vue
- [ ] T056 [US2] Run all User Story 2 tests and verify they pass (GREEN phase)
- [ ] T057 [US2] Refactor loop logic if needed (REFACTOR phase)
- [ ] T058 [US2] Verify test coverage meets 80% threshold for US2 files

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently. Single video loops automatically.

---

## Phase 5: User Story 3 - Basic Playback Controls (Priority: P2)

**Goal**: 使用者能夠暫停、繼續播放和調整音量

**Independent Test**: 播放影片，測試暫停、繼續和音量調整按鈕，所有控制立即響應

### Tests for User Story 3 (TDD - WRITE TESTS FIRST) ⚠️

- [ ] T059 [P] [US3] Unit test for play/pause control in tests/unit/useYouTubePlayer.test.js
- [ ] T060 [P] [US3] Unit test for volume control and mute in tests/unit/useYouTubePlayer.test.js
- [ ] T061 [P] [US3] Integration test for preferences persistence (volume, mute) in tests/integration/preferences-persistence.test.js
- [ ] T062 [US3] Verify all User Story 3 tests fail initially (RED phase)

### Implementation for User Story 3

- [ ] T063 [P] [US3] Create src/components/PlayerControls.vue (play/pause button, volume slider, mute button)
- [ ] T064 [US3] Implement play() method in useYouTubePlayer.js (calls player.playVideo())
- [ ] T065 [US3] Implement pause() method in useYouTubePlayer.js (calls player.pauseVideo())
- [ ] T066 [US3] Implement setVolume(volume) method in useYouTubePlayer.js (calls player.setVolume(), validates 0-100)
- [ ] T067 [US3] Implement mute()/unmute() methods in useYouTubePlayer.js
- [ ] T068 [US3] Add volume and isMuted reactive refs to useYouTubePlayer.js
- [ ] T069 [US3] Persist volume and isMuted to UserPreferences (useLocalStorage) in App.vue
- [ ] T070 [US3] Wire PlayerControls events to useYouTubePlayer methods in App.vue
- [ ] T071 [US3] Apply UserPreferences (volume, mute) to player in onPlayerReady handler
- [ ] T072 [US3] Add visual feedback (<100ms) to PlayerControls buttons (active states, transitions)
- [ ] T073 [US3] Integrate PlayerControls component into App.vue layout
- [ ] T074 [US3] Run all User Story 3 tests and verify they pass (GREEN phase)
- [ ] T075 [US3] Refactor controls logic if needed (REFACTOR phase)
- [ ] T076 [US3] Verify test coverage meets 80% threshold for US3 files

**Checkpoint**: At this point, User Stories 1, 2, AND 3 should all work independently. User has full playback control.

---

## Phase 6: User Story 4 - Loop Toggle Control (Priority: P3)

**Goal**: 使用者能夠開啟或關閉循環播放功能

**Independent Test**: 切換循環開關，觀察影片結束後行為變化（循環開啟：重播，循環關閉：停止）

### Tests for User Story 4 (TDD - WRITE TESTS FIRST) ⚠️

- [ ] T077 [P] [US4] Unit test for loop toggle functionality in tests/unit/useYouTubePlayer.test.js
- [ ] T078 [P] [US4] Integration test for loop off behavior (video stops at end) in tests/integration/video-loop.test.js
- [ ] T079 [US4] Verify all User Story 4 tests fail initially (RED phase)

### Implementation for User Story 4

- [ ] T080 [P] [US4] Create src/components/LoopToggle.vue (toggle button, visual state indicator)
- [ ] T081 [US4] Implement toggleLoop() method in useYouTubePlayer.js (flips loopEnabled boolean)
- [ ] T082 [US4] Update handleVideoEnd in useYouTubePlayer.js to respect loopEnabled=false (stop playback instead of loop)
- [ ] T083 [US4] Wire LoopToggle click event to toggleLoop method in App.vue
- [ ] T084 [US4] Bind LoopToggle visual state to loopEnabled ref (show "on" or "off" indicator)
- [ ] T085 [US4] Persist loopEnabled changes to UserPreferences (useLocalStorage)
- [ ] T086 [US4] Load loopEnabled from LocalStorage on app initialization (apply to player)
- [ ] T087 [US4] Integrate LoopToggle component into App.vue layout
- [ ] T088 [US4] Run all User Story 4 tests and verify they pass (GREEN phase)
- [ ] T089 [US4] Refactor toggle logic if needed (REFACTOR phase)
- [ ] T090 [US4] Verify test coverage meets 80% threshold for US4 files

**Checkpoint**: All user stories should now be independently functional. User can toggle loop on/off.

---

## Phase 7: Playlist Support (Extended from User Stories)

**Goal**: 支援 YouTube 播放清單網址，循環播放整個清單

**Independent Test**: 貼上播放清單網址，驗證所有影片依序播放，最後一個影片結束後從第一個重新開始

### Tests for Playlist Support (TDD - WRITE TESTS FIRST) ⚠️

- [ ] T091 [P] [PL] Unit test for extractPlaylistId function in tests/unit/useUrlParser.test.js
- [ ] T092 [P] [PL] Unit test for usePlaylist composable in tests/unit/usePlaylist.test.js
- [ ] T093 [P] [PL] Integration test for playlist loop behavior in tests/integration/playlist-loop.test.js
- [ ] T094 [P] [PL] Integration test for skipping unavailable videos in playlist in tests/integration/playlist-loop.test.js
- [ ] T095 [PL] Verify all Playlist tests fail initially (RED phase)

### Implementation for Playlist Support

- [ ] T096 [PL] Extend useUrlParser.js with extractPlaylistId function (use URL.searchParams.get('list'))
- [ ] T097 [PL] Create src/composables/usePlaylist.js implementing playlistSession management
- [ ] T098 [PL] Implement loadPlaylist(playlistId) in usePlaylist.js (calls player.loadPlaylist({list: playlistId}))
- [ ] T099 [PL] Implement getPlaylistInfo() in usePlaylist.js (getPlaylist(), getPlaylistIndex())
- [ ] T100 [PL] Implement nextVideo() in usePlaylist.js (calls player.nextVideo())
- [ ] T101 [PL] Implement playVideoAt(index) in usePlaylist.js (calls player.playVideoAt(index))
- [ ] T102 [PL] Extend handleVideoEnd in useYouTubePlayer.js for playlist loop logic (check currentIndex, playVideoAt(0) if last video)
- [ ] T103 [PL] Extend onError handler in useYouTubePlayer.js to skip unavailable playlist videos (add to skippedIndices, call nextVideo())
- [ ] T104 [P] [PL] Create src/components/PlaylistInfo.vue (display "第 X 個影片，共 Y 個")
- [ ] T105 [PL] Wire PlaylistInfo to playlistSession state in App.vue (show only when in playlist mode)
- [ ] T106 [PL] Update UrlInput parsing logic to detect and load playlists in App.vue
- [ ] T107 [PL] Run all Playlist tests and verify they pass (GREEN phase)
- [ ] T108 [PL] Refactor playlist logic if needed (REFACTOR phase)
- [ ] T109 [PL] Verify test coverage meets 80% threshold for playlist files

**Checkpoint**: Playlist support complete. User can paste playlist URLs and experience seamless looping.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories

- [ ] T110 [P] Add ARIA labels to all interactive elements for accessibility (UrlInput, PlayerControls, LoopToggle)
- [ ] T111 [P] Implement keyboard shortcuts (Space = play/pause, M = mute, Arrow keys = volume)
- [ ] T112 [P] Add loading indicator animation to VideoPlayer.vue (spinner, "載入中..." text)
- [ ] T113 [P] Style ErrorMessage.vue with proper colors, icons, and animations
- [ ] T114 [P] Make layout responsive for mobile devices (CSS media queries)
- [ ] T115 [P] Add focus management for better keyboard navigation
- [ ] T116 Code cleanup: Remove console.logs, fix ESLint warnings (npm run lint:fix)
- [ ] T117 Performance optimization: Check bundle size (npm run build, verify <500KB)
- [ ] T118 Performance optimization: Lazy load non-critical components if needed
- [ ] T119 Run full test suite and verify 80% coverage (npm run test:coverage)
- [ ] T120 Run accessibility audit (Lighthouse or axe DevTools, verify WCAG 2.1 Level AA)
- [ ] T121 Create README.md with project description, setup instructions, usage guide
- [ ] T122 Test in target browsers (Chrome 90+, Firefox 88+, Safari 14+, Edge 90+)
- [ ] T123 Run E2E tests with Playwright (tests/e2e/critical-paths.test.js)
- [ ] T124 Fix any E2E test failures
- [ ] T125 Final production build test (npm run build && npm run preview)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Story 1 (Phase 3)**: Depends on Foundational (Phase 2) - MVP starts here
- **User Story 2 (Phase 4)**: Can start after Foundational, does NOT depend on US1 (but logically extends US1)
- **User Story 3 (Phase 5)**: Can start after Foundational, does NOT depend on US1 or US2
- **User Story 4 (Phase 6)**: Can start after Foundational, builds on US2 (loop logic)
- **Playlist Support (Phase 7)**: Can start after Foundational, extends US1 and US2
- **Polish (Phase 8)**: Depends on desired user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - ✅ INDEPENDENT, no dependencies on other stories
- **User Story 2 (P1)**: Can start after Foundational (Phase 2) - ✅ INDEPENDENT, but extends US1 functionality
- **User Story 3 (P2)**: Can start after Foundational (Phase 2) - ✅ INDEPENDENT
- **User Story 4 (P3)**: Can start after Foundational (Phase 2) - Logically depends on US2 (loop logic)
- **Playlist Support**: Can start after Foundational (Phase 2) - Extends US1 and US2

### Within Each User Story

**TDD Workflow (CRITICAL)**:
1. **RED**: Tests written and FAIL before implementation
2. **GREEN**: Implementation makes tests pass
3. **REFACTOR**: Code cleanup while keeping tests green

**Execution Order**:
- Tests (if included) MUST be written and FAIL before implementation
- Composables before components (business logic before UI)
- Components in order of dependency (utils → composables → components → App.vue integration)
- Story complete before moving to next priority

### Parallel Opportunities

- All Setup tasks marked [P] can run in parallel (T003, T004, T005, T006, T007, T010, T011)
- All Foundational tasks marked [P] can run in parallel (T019)
- Once Foundational phase completes, user stories CAN be worked on in parallel by different developers:
  - Developer A: User Story 1 (T025-T047)
  - Developer B: User Story 3 (T059-T076) - Independent
  - Developer C: Playlist Support (T091-T109) - If confident in US1/US2 patterns
- Tests within a story marked [P] can run in parallel (T025, T026, T027, T028, T029, T030)
- Components within a story marked [P] can run in parallel (T039, T040, T041)
- Polish tasks marked [P] can run in parallel (T110, T111, T112, T113, T114, T115)

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together (RED phase):
Task T025: "Contract test for YouTube IFrame API onReady event"
Task T026: "Contract test for YouTube IFrame API onStateChange event"
Task T027: "Contract test for YouTube IFrame API onError event"
Task T028: "Unit test for useUrlParser composable"
Task T029: "Unit test for useYouTubePlayer composable"
Task T030: "Integration test for player lifecycle"

# Launch all component creation for User Story 1 together (GREEN phase):
Task T039: "Create UrlInput.vue"
Task T040: "Create VideoPlayer.vue"
Task T041: "Create ErrorMessage.vue"
```

---

## Implementation Strategy

### MVP First (User Stories 1 + 2 Only)

1. Complete Phase 1: Setup (T001-T018)
2. Complete Phase 2: Foundational (T019-T024) - ✅ CRITICAL GATE
3. Complete Phase 3: User Story 1 (T025-T047) - Paste and play
4. Complete Phase 4: User Story 2 (T048-T058) - Auto loop
5. **STOP and VALIDATE**: Test US1+US2 independently, verify core MVP works
6. Deploy/demo if ready (basic YouTube loop player)

### Incremental Delivery

1. Complete Setup + Foundational (T001-T024) → Foundation ready ✅
2. Add User Story 1 (T025-T047) → Test independently → Can paste and play ✅
3. Add User Story 2 (T048-T058) → Test independently → Auto-loop works ✅ **MVP COMPLETE**
4. Add User Story 3 (T059-T076) → Test independently → Playback controls ✅
5. Add User Story 4 (T077-T090) → Test independently → Loop toggle ✅
6. Add Playlist Support (T091-T109) → Test independently → Full feature set ✅
7. Polish (T110-T125) → Production ready 🚀

Each increment adds value without breaking previous features.

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together (T001-T024)
2. Once Foundational is done:
   - Developer A: User Story 1 (T025-T047) - Core MVP
   - Developer B: User Story 3 (T059-T076) - Playback controls (independent)
   - Developer C: Setup Playlist infrastructure (T091-T095 tests only)
3. After US1 complete:
   - Developer A: User Story 2 (T048-T058) - Auto loop (extends US1)
   - Developer B: Continue US3
   - Developer C: Implement Playlist (T096-T109, depends on US1 patterns)
4. Stories complete and integrate independently

---

## Notes

- **[P]** tasks = different files, no dependencies
- **[Story]** label maps task to specific user story for traceability
- Each user story should be independently completable and testable
- **TDD is MANDATORY**: Verify tests fail (RED) before implementing (GREEN), then refactor
- **80% code coverage required** per constitution
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
- Avoid: vague tasks, same file conflicts, cross-story dependencies that break independence
- **Tests are REQUIRED** for this project (constitution principle II)

---

## Test Coverage Targets

Per Constitution requirement II (80% minimum coverage):

| Component Type | Coverage Target | Files |
|---------------|----------------|-------|
| Utils | 90%+ | urlValidator.js, errorMessages.js |
| Composables | 85%+ | useYouTubePlayer.js, useUrlParser.js, useLocalStorage.js, usePlaylist.js |
| Components | 75%+ | UrlInput.vue, VideoPlayer.vue, PlayerControls.vue, LoopToggle.vue, PlaylistInfo.vue, ErrorMessage.vue |
| Integration | N/A | Full user journey coverage (paste → play → loop) |
| Overall | 80%+ | All src/ files combined |

**Enforcement**: Task T119 and T047, T058, T076, T090, T109 verify coverage thresholds before story completion.
