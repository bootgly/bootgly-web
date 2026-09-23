<?php

// The Web platform's agent skills — laid down by `bootgly kit boot` into a
// kit's `projects/.agents/skills/` while this package is set up there.


use Bootgly\ACI\Tests\Suite\Test;


return new Test(
   description: 'The Web package ships its bootgly-*-web agent skills: well-formed, within budget, tracked, naming only real classes and kit-shaped links',
   test: function () {
      $shelf = WEB_ROOT_BASE . '/Web/templates/projects/.agents/skills';
      // ! The stamp `kit boot` owns a skill by: the first line after its frontmatter
      $stamp = class_exists('Bootgly\\commands\\KitCommand') === true && defined('Bootgly\\commands\\KitCommand::STAMP') === true
         ? (string) constant('Bootgly\\commands\\KitCommand::STAMP')
         : '<!-- Machine-managed by `bootgly kit boot`';
      $sections = [
         'Architecture_principles',
         'Coding_styles',
         'Naming_conventions',
         'Organizational_structures',
         'Testing_guidelines',
         'Workflow_pipelines',
      ];

      // @@ Each skill: named for this platform (`kit boot` lays nothing else from
      //    here), agentskills.io frontmatter a strict YAML parser reads whole
      //    (name = folder, a double-quoted description of 1 to 1024 characters
      //    that is also a JSON string, no other key), a body within 200 lines,
      //    each references/*.md within 250
      $folders = array_map('strval', (array) glob("{$shelf}/*", GLOB_ONLYDIR));
      $malformed = [];
      $documents = [];
      foreach ($folders as $folder) {
         $name = basename($folder);
         $skill = (string) @file_get_contents("{$folder}/SKILL.md");
         $documents["{$name}/SKILL.md"] = $skill;
         $framed = preg_match('/\A---\n(.*?)\n---\n(.*)\z/s', $skill, $parts) === 1;
         preg_match_all('/^([a-z][a-z-]*):/m', $framed ? $parts[1] : '', $keys);
         preg_match('/^name: ([a-z0-9-]+)$/m', $framed ? $parts[1] : '', $named);
         preg_match('/^description: ("(?:[^"\\\\]|\\\\.)*")$/m', $framed ? $parts[1] : '', $quoted);
         $described = json_decode($quoted[1] ?? 'null');
         $body = $framed ? substr_count(trim($parts[2]), "\n") + 1 : 0;
         $stamped = preg_match('/\A---\n(?:(?!---\n)[^\n]*\n)*---\n' . preg_quote($stamp, '/') . '/', $skill) === 1;
         if ($framed === false || $keys[1] !== ['name', 'description'] || ($named[1] ?? '') !== $name
            || preg_match('/^bootgly-[a-z0-9-]+-web$/', $name) !== 1 || is_string($described) === false
            || strlen($described) === 0 || strlen($described) > 1024 || $body > 200 || $stamped === false) {
            $malformed[] = $name;
         }
         foreach ((array) glob("{$folder}/references/*.md") as $reference) {
            $content = (string) file_get_contents((string) $reference);
            $documents["{$name}/references/" . basename((string) $reference)] = $content;
            if (substr_count(trim($content), "\n") + 1 > 250) {
               $malformed[] = "{$name}/references/" . basename((string) $reference);
            }
         }
      }
      yield assert(
         assertion: in_array("{$shelf}/bootgly-build-web", $folders, true) && $malformed === [],
         description: 'bootgly-build-web ships, and every skill is a bootgly-<action>-web with name = folder, a double-quoted description of 1 to 1024 characters, no other key, the stamp right after, a body within 200 lines and references within 250, malformed: '
            . json_encode($malformed)
      );

      // @ Tracked: never swallowed by an ignore rule
      if (function_exists('exec') === true && (is_dir(WEB_ROOT_BASE . '/.git') === true || is_file(WEB_ROOT_BASE . '/.git') === true)) {
         $ignored = [];
         foreach (array_keys($documents) as $document) {
            $status = -1;
            $output = [];
            exec(
               'git -C ' . escapeshellarg(WEB_ROOT_BASE) . ' check-ignore -q '
                  . escapeshellarg("Web/templates/projects/.agents/skills/{$document}") . ' 2>/dev/null',
               $output,
               $status
            );
            if ($status === 0) {
               $ignored[] = $document;
            }
         }
         yield assert(
            assertion: $ignored === [],
            description: 'no skill file is ignored by the package .gitignore, ignored: ' . json_encode($ignored)
         );
      }

      // @@ Every relative link resolves once laid down in a kit: inside the
      //    skill, to the framework's base skill, or to a kit rule file
      $broken = [];
      foreach ($documents as $document => $content) {
         preg_match_all('/\]\(([^)\s]+)\)/', $content, $links);
         $folder = dirname("{$shelf}/{$document}");
         $depth = str_contains($document, '/references/') ? '../' : '';
         foreach ($links[1] as $link) {
            if (preg_match('#^https?://#', $link) === 1) {
               continue;
            }
            $inside = str_starts_with($link, '../') === false || ($depth !== '' && preg_match('#^\.\./[^.]#', $link) === 1);
            $valid = match (true) {
               $inside                                                                                  => is_file("{$folder}/{$link}"),
               in_array($link, ["{$depth}../bootgly-build/SKILL.md", "{$depth}../bootgly-build/references/database.md"], true) => true,
               preg_match('#^' . preg_quote("{$depth}../../../.agents/rules/", '#') . '([A-Za-z_]+)\.md$#', $link, $section) === 1
                  => in_array($section[1], $sections, true),
               default                                                                                  => false,
            };
            if ($valid === false) {
               $broken[] = "{$document}: {$link}";
            }
         }
      }
      yield assert(
         assertion: $broken === [],
         description: 'every relative link resolves in a kit (the skill, the base bootgly-build skill, a rule file), broken: ' . json_encode($broken)
      );

      // @@ Every class the skills name — inline or imported — exists
      $text = implode("\n", $documents);
      preg_match_all('/`((?:Bootgly|Web)(?:\\\\[A-Za-z_]+){2,})`|^use ((?:Bootgly|Web)(?:\\\\[A-Za-z_]+)+)(?: as \w+)?;$/m', $text, $found);
      $classes = array_values(array_unique(array_filter([...$found[1], ...$found[2]])));
      $absent = [];
      foreach ($classes as $class) {
         if (class_exists($class) === false && interface_exists($class) === false && enum_exists($class) === false) {
            $absent[] = $class;
         }
      }
      yield assert(
         assertion: $classes !== [] && $absent === [],
         description: 'every Web and Bootgly class the skills name exists, absent: ' . json_encode($absent)
      );
   }
);
